<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\BankConnection;
use App\Modules\Finance\Services\Banking\BankSyncService;
use App\Modules\Finance\Services\Banking\IngBankingService;
use Illuminate\Http\Request;

class BankConnectionController extends Controller
{
    public function __construct(
        private IngBankingService $ingService,
        private BankSyncService $syncService,
    ) {}

    public function index(Request $request)
    {
        $connections = BankConnection::where('user_id', $request->user()->id)
            ->with('accounts')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'provider' => $c->provider,
                    'status' => $c->status,
                    'last_synced_at' => $c->last_synced_at,
                    'token_expires_at' => $c->token_expires_at,
                    'accounts_count' => $c->accounts->count(),
                    'created_at' => $c->created_at,
                ];
            });

        return response()->json(['data' => $connections]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|string',
            'tls_certificate' => 'required|string',
            'tls_key' => 'required|string',
            'signing_certificate' => 'required|string',
            'signing_key' => 'required|string',
        ]);

        $connection = $this->ingService->initiateConnection(
            $request->user(),
            $validated['client_id'],
            [
                'tls_certificate' => $validated['tls_certificate'],
                'tls_key' => $validated['tls_key'],
                'signing_certificate' => $validated['signing_certificate'],
                'signing_key' => $validated['signing_key'],
            ]
        );

        return response()->json([
            'data' => [
                'id' => $connection->id,
                'status' => $connection->status,
                'provider' => $connection->provider,
            ],
        ], 201);
    }

    public function show(Request $request, BankConnection $connection)
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        return response()->json([
            'data' => [
                'id' => $connection->id,
                'provider' => $connection->provider,
                'status' => $connection->status,
                'last_synced_at' => $connection->last_synced_at,
                'token_expires_at' => $connection->token_expires_at,
                'accounts' => $connection->accounts,
            ],
        ]);
    }

    public function destroy(Request $request, BankConnection $connection)
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        $connection->update(['status' => 'revoked']);
        $connection->delete();

        return response()->json(['message' => 'Conexión bancaria eliminada']);
    }

    public function authorize(Request $request, BankConnection $connection)
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        $redirectUri = url('/api/finance/banking/callback');
        $authUrl = $this->ingService->getAuthorizationUrl($connection, $redirectUri);

        return response()->json(['data' => ['authorization_url' => $authUrl]]);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');
        $connectionId = $request->get('state');

        if (!$code || !$connectionId) {
            return redirect('/finance/accounts?banking_error=missing_code');
        }

        $connection = BankConnection::findOrFail($connectionId);
        abort_unless($connection->user_id === $request->user()->id, 403);

        try {
            $this->ingService->handleCallback($connection, $code);
            $this->syncService->fullSync($connection);

            return redirect('/finance/accounts?banking_success=true');
        } catch (\Exception $e) {
            return redirect('/finance/accounts?banking_error=' . urlencode($e->getMessage()));
        }
    }

    public function sync(Request $request, BankConnection $connection)
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        if (!$connection->isActive()) {
            return response()->json(['error' => 'La conexión no está activa o el token ha expirado.'], 422);
        }

        $result = $this->syncService->fullSync($connection);

        return response()->json(['data' => $result]);
    }
}
