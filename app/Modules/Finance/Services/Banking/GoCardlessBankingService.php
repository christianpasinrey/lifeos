<?php

namespace App\Modules\Finance\Services\Banking;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoCardlessBankingService
{
    private string $baseUrl;
    private string $secretId;
    private string $secretKey;
    private string $country;

    public function __construct()
    {
        $this->baseUrl = config('services.gocardless.base_url');
        $this->secretId = config('services.gocardless.secret_id');
        $this->secretKey = config('services.gocardless.secret_key');
        $this->country = config('services.gocardless.country', 'ES');
    }

    /**
     * Get or refresh app-level access token (cached).
     */
    public function getAccessToken(): string
    {
        return Cache::remember('gocardless_access_token', 86000, function () {
            $response = Http::baseUrl($this->baseUrl)
                ->acceptJson()
                ->post('/token/new/', [
                    'secret_id' => $this->secretId,
                    'secret_key' => $this->secretKey,
                ]);

            if ($response->failed()) {
                Log::error('GoCardless: failed to get access token', ['response' => $response->body()]);
                throw new \RuntimeException('Failed to authenticate with GoCardless');
            }

            $data = $response->json();

            // Cache refresh token separately
            if (!empty($data['refresh'])) {
                Cache::put('gocardless_refresh_token', $data['refresh'], $data['refresh_expires'] ?? 86400);
            }

            return $data['access'];
        });
    }

    /**
     * Refresh an expired access token.
     */
    public function refreshAccessToken(): string
    {
        $refreshToken = Cache::get('gocardless_refresh_token');

        if (!$refreshToken) {
            Cache::forget('gocardless_access_token');
            return $this->getAccessToken();
        }

        $response = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->post('/token/refresh/', [
                'refresh' => $refreshToken,
            ]);

        if ($response->failed()) {
            Cache::forget('gocardless_access_token');
            Cache::forget('gocardless_refresh_token');
            return $this->getAccessToken();
        }

        $data = $response->json();
        $token = $data['access'];

        Cache::put('gocardless_access_token', $token, $data['access_expires'] ?? 86000);

        return $token;
    }

    /**
     * Get available banking institutions for the configured country.
     */
    public function getInstitutions(?string $country = null): array
    {
        $country = $country ?? $this->country;

        return Cache::remember("gocardless_institutions_{$country}", 86400, function () use ($country) {
            $response = $this->request()->get('/institutions/', [
                'country' => $country,
            ]);

            return $response->json() ?? [];
        });
    }

    /**
     * Create an end-user agreement for a specific institution.
     */
    public function createAgreement(string $institutionId, int $maxHistoricalDays = 180, int $accessValidDays = 90): array
    {
        $response = $this->request()->post('/agreements/enduser/', [
            'institution_id' => $institutionId,
            'max_historical_days' => $maxHistoricalDays,
            'access_valid_for_days' => $accessValidDays,
            'access_scope' => ['balances', 'details', 'transactions'],
        ]);

        if ($response->failed()) {
            Log::error('GoCardless: failed to create agreement', [
                'institution' => $institutionId,
                'response' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to create bank agreement: ' . $this->extractError($response));
        }

        return $response->json();
    }

    /**
     * Create a requisition (link) for user to authorize bank access.
     */
    public function createRequisition(
        string $institutionId,
        string $redirect,
        string $agreementId,
        string $reference,
        string $userLanguage = 'ES',
    ): array {
        $response = $this->request()->post('/requisitions/', [
            'institution_id' => $institutionId,
            'redirect' => $redirect,
            'agreement' => $agreementId,
            'reference' => $reference,
            'user_language' => $userLanguage,
        ]);

        if ($response->failed()) {
            Log::error('GoCardless: failed to create requisition', [
                'institution' => $institutionId,
                'response' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to create bank link: ' . $this->extractError($response));
        }

        return $response->json();
    }

    /**
     * Get requisition status and linked accounts.
     */
    public function getRequisition(string $requisitionId): array
    {
        $response = $this->request()->get("/requisitions/{$requisitionId}/");

        if ($response->failed()) {
            throw new \RuntimeException('Failed to get requisition status');
        }

        return $response->json();
    }

    /**
     * Delete a requisition (revoke access).
     */
    public function deleteRequisition(string $requisitionId): void
    {
        $this->request()->delete("/requisitions/{$requisitionId}/");
    }

    /**
     * Get account metadata (status, IBAN).
     */
    public function getAccountMetadata(string $accountId): array
    {
        $response = $this->request()->get("/accounts/{$accountId}/");

        if ($response->failed()) {
            throw new \RuntimeException("Failed to get account metadata for {$accountId}");
        }

        return $response->json();
    }

    /**
     * Get account details (ownerName, IBAN, currency).
     */
    public function getAccountDetails(string $accountId): array
    {
        $response = $this->request()->get("/accounts/{$accountId}/details/");

        if ($response->failed()) {
            throw new \RuntimeException("Failed to get account details for {$accountId}");
        }

        return $response->json()['account'] ?? $response->json();
    }

    /**
     * Get account balances.
     */
    public function getAccountBalances(string $accountId): array
    {
        $response = $this->request()->get("/accounts/{$accountId}/balances/");

        if ($response->failed()) {
            throw new \RuntimeException("Failed to get account balances for {$accountId}");
        }

        return $response->json()['balances'] ?? [];
    }

    /**
     * Get account transactions.
     */
    public function fetchTransactions(string $accountId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $params = [];
        if ($dateFrom) $params['date_from'] = $dateFrom;
        if ($dateTo) $params['date_to'] = $dateTo;

        $response = $this->request()->get("/accounts/{$accountId}/transactions/", $params);

        if ($response->failed()) {
            throw new \RuntimeException("Failed to get transactions for {$accountId}");
        }

        return $response->json()['transactions'] ?? [];
    }

    /**
     * Map GoCardless requisition status to BankConnection status.
     */
    public function mapRequisitionStatus(string $gcStatus): string
    {
        return match ($gcStatus) {
            'CR', 'GA', 'SA', 'GR' => 'pending',
            'LN' => 'active',
            'EX' => 'expired',
            'RJ' => 'revoked',
            'SU' => 'suspended',
            default => 'pending',
        };
    }

    /**
     * Build authenticated HTTP request.
     */
    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->getAccessToken())
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Extract human-readable error from GoCardless response.
     */
    private function extractError($response): string
    {
        $data = $response->json();

        if (isset($data['detail'])) {
            return $data['detail'];
        }

        if (isset($data['summary'])) {
            return $data['summary'];
        }

        return $response->body();
    }
}
