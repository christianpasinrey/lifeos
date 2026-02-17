<?php

namespace App\Modules\Finance\Services\Banking;

use App\Models\User;
use App\Modules\Finance\Models\BankConnection;
use App\Modules\Finance\Models\BankConnectionCertificate;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IngBankingService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ing.sandbox', true)
            ? 'https://api.sandbox.ing.com'
            : 'https://api.ing.com';
    }

    /**
     * Step 1: Store credentials and initiate connection.
     */
    public function initiateConnection(User $user, string $clientId, array $certificates): BankConnection
    {
        $connection = BankConnection::create([
            'user_id' => $user->id,
            'provider' => 'ing',
            'client_id' => $clientId,
            'status' => 'pending',
        ]);

        BankConnectionCertificate::create([
            'bank_connection_id' => $connection->id,
            'tls_certificate' => $certificates['tls_certificate'],
            'tls_key' => $certificates['tls_key'],
            'signing_certificate' => $certificates['signing_certificate'],
            'signing_key' => $certificates['signing_key'],
        ]);

        // Get application access token
        $this->getApplicationToken($connection);

        return $connection->fresh('certificates');
    }

    /**
     * Step 2: Get application access token (client_credentials grant).
     */
    public function getApplicationToken(BankConnection $connection): string
    {
        $client = $this->buildHttpClient($connection);

        $body = 'grant_type=client_credentials';
        $date = gmdate('D, d M Y H:i:s T');
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
        $reqId = Str::uuid()->toString();

        $signingString = "(request-target): post /oauth2/token\ndate: {$date}\ndigest: {$digest}\nx-ing-reqid: {$reqId}";
        $signature = $this->sign($connection, $signingString);

        $keyId = $connection->client_id;
        $authHeader = "Signature keyId=\"{$keyId}\",algorithm=\"rsa-sha256\",headers=\"(request-target) date digest x-ing-reqid\",signature=\"{$signature}\"";

        $response = $client->post('/oauth2/token', [
            'headers' => [
                'Date' => $date,
                'Digest' => $digest,
                'X-ING-ReqID' => $reqId,
                'Authorization' => $authHeader,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => $body,
        ]);

        $data = json_decode($response->getBody(), true);

        $connection->update([
            'access_token' => $data['access_token'],
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 900),
            'metadata' => array_merge($connection->metadata ?? [], [
                'app_token' => true,
            ]),
        ]);

        return $data['access_token'];
    }

    /**
     * Step 3: Get authorization URL for user consent.
     */
    public function getAuthorizationUrl(BankConnection $connection, string $redirectUri): string
    {
        $client = $this->buildHttpClient($connection);
        $token = $this->ensureValidToken($connection);

        $scope = 'payment_accounts:balances:view payment_accounts:transactions:view';
        $params = http_build_query([
            'scope' => $scope,
            'redirect_uri' => $redirectUri,
            'country_code' => 'ES',
        ]);

        $date = gmdate('D, d M Y H:i:s T');
        $reqId = Str::uuid()->toString();
        $digest = 'SHA-256=' . base64_encode(hash('sha256', '', true));

        $path = "/oauth2/authorization-server-url?{$params}";
        $signingString = "(request-target): get {$path}\ndate: {$date}\ndigest: {$digest}\nx-ing-reqid: {$reqId}";
        $signature = $this->sign($connection, $signingString);

        $keyId = $connection->client_id;
        $sigHeader = "keyId=\"{$keyId}\",algorithm=\"rsa-sha256\",headers=\"(request-target) date digest x-ing-reqid\",signature=\"{$signature}\"";

        $response = $client->get($path, [
            'headers' => [
                'Date' => $date,
                'Digest' => $digest,
                'X-ING-ReqID' => $reqId,
                'Authorization' => "Bearer {$token}",
                'Signature' => $sigHeader,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        return $data['location'];
    }

    /**
     * Step 4: Exchange authorization code for customer token.
     */
    public function handleCallback(BankConnection $connection, string $authCode): void
    {
        $client = $this->buildHttpClient($connection);

        $body = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $authCode,
        ]);

        $date = gmdate('D, d M Y H:i:s T');
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
        $reqId = Str::uuid()->toString();

        $signingString = "(request-target): post /oauth2/token\ndate: {$date}\ndigest: {$digest}\nx-ing-reqid: {$reqId}";
        $signature = $this->sign($connection, $signingString);

        $keyId = $connection->client_id;
        $authHeader = "Signature keyId=\"{$keyId}\",algorithm=\"rsa-sha256\",headers=\"(request-target) date digest x-ing-reqid\",signature=\"{$signature}\"";

        $response = $client->post('/oauth2/token', [
            'headers' => [
                'Date' => $date,
                'Digest' => $digest,
                'X-ING-ReqID' => $reqId,
                'Authorization' => $authHeader,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => $body,
        ]);

        $data = json_decode($response->getBody(), true);

        $connection->update([
            'status' => 'active',
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);
    }

    /**
     * Fetch accounts from ING.
     */
    public function fetchAccounts(BankConnection $connection): array
    {
        $token = $this->ensureValidToken($connection);
        $client = $this->buildHttpClient($connection);

        $response = $this->signedGet($client, $connection, $token, '/v1/accounts');

        return json_decode($response->getBody(), true)['accounts'] ?? [];
    }

    /**
     * Fetch balances for an account.
     */
    public function fetchBalances(BankConnection $connection, string $externalAccountId): array
    {
        $token = $this->ensureValidToken($connection);
        $client = $this->buildHttpClient($connection);

        $response = $this->signedGet($client, $connection, $token, "/v1/accounts/{$externalAccountId}/balances");

        return json_decode($response->getBody(), true)['balances'] ?? [];
    }

    /**
     * Fetch transactions for an account.
     */
    public function fetchTransactions(BankConnection $connection, string $externalAccountId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $token = $this->ensureValidToken($connection);
        $client = $this->buildHttpClient($connection);

        $params = [];
        if ($dateFrom) $params['dateFrom'] = $dateFrom;
        if ($dateTo) $params['dateTo'] = $dateTo;

        $path = "/v1/accounts/{$externalAccountId}/transactions";
        if ($params) {
            $path .= '?' . http_build_query($params);
        }

        $response = $this->signedGet($client, $connection, $token, $path);

        return json_decode($response->getBody(), true)['transactions'] ?? [];
    }

    /**
     * Create a Guzzle client with mTLS certificates.
     */
    public function buildHttpClient(BankConnection $connection): Client
    {
        $certs = $connection->certificates;

        // Write certificates to temp files for Guzzle
        $tlsCertPath = tempnam(sys_get_temp_dir(), 'ing_tls_cert_');
        $tlsKeyPath = tempnam(sys_get_temp_dir(), 'ing_tls_key_');

        file_put_contents($tlsCertPath, $certs->tls_certificate);
        file_put_contents($tlsKeyPath, $certs->tls_key);

        return new Client([
            'base_uri' => $this->baseUrl,
            'cert' => $tlsCertPath,
            'ssl_key' => $tlsKeyPath,
            'timeout' => 30,
            'http_errors' => true,
        ]);
    }

    /**
     * Sign a string with the signing key (RSA-SHA256).
     */
    private function sign(BankConnection $connection, string $signingString): string
    {
        $certs = $connection->certificates;
        $privateKey = openssl_pkey_get_private($certs->signing_key);

        openssl_sign($signingString, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    /**
     * Make a signed GET request.
     */
    private function signedGet(Client $client, BankConnection $connection, string $token, string $path)
    {
        $date = gmdate('D, d M Y H:i:s T');
        $digest = 'SHA-256=' . base64_encode(hash('sha256', '', true));
        $reqId = Str::uuid()->toString();

        $signingString = "(request-target): get {$path}\ndate: {$date}\ndigest: {$digest}\nx-ing-reqid: {$reqId}";
        $signature = $this->sign($connection, $signingString);

        $keyId = $connection->client_id;
        $sigHeader = "keyId=\"{$keyId}\",algorithm=\"rsa-sha256\",headers=\"(request-target) date digest x-ing-reqid\",signature=\"{$signature}\"";

        return $client->get($path, [
            'headers' => [
                'Date' => $date,
                'Digest' => $digest,
                'X-ING-ReqID' => $reqId,
                'Authorization' => "Bearer {$token}",
                'Signature' => $sigHeader,
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Ensure we have a valid token, refresh if needed.
     */
    private function ensureValidToken(BankConnection $connection): string
    {
        if (!$connection->isTokenExpired()) {
            return $connection->access_token;
        }

        if ($connection->refresh_token) {
            return $this->refreshToken($connection);
        }

        return $this->getApplicationToken($connection);
    }

    /**
     * Refresh an expired customer token.
     */
    public function refreshToken(BankConnection $connection): string
    {
        $client = $this->buildHttpClient($connection);

        $body = http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
        ]);

        $date = gmdate('D, d M Y H:i:s T');
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
        $reqId = Str::uuid()->toString();

        $signingString = "(request-target): post /oauth2/token\ndate: {$date}\ndigest: {$digest}\nx-ing-reqid: {$reqId}";
        $signature = $this->sign($connection, $signingString);

        $keyId = $connection->client_id;
        $authHeader = "Signature keyId=\"{$keyId}\",algorithm=\"rsa-sha256\",headers=\"(request-target) date digest x-ing-reqid\",signature=\"{$signature}\"";

        try {
            $response = $client->post('/oauth2/token', [
                'headers' => [
                    'Date' => $date,
                    'Digest' => $digest,
                    'X-ING-ReqID' => $reqId,
                    'Authorization' => $authHeader,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $body,
            ]);

            $data = json_decode($response->getBody(), true);

            $connection->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $connection->refresh_token,
                'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            ]);

            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('ING token refresh failed', ['error' => $e->getMessage()]);
            $connection->update(['status' => 'expired']);
            throw $e;
        }
    }
}
