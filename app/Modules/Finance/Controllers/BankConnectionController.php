<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\BankConnection;
use App\Modules\Finance\Services\Banking\BankSyncService;
use App\Modules\Finance\Services\Banking\GoCardlessBankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BankConnectionController extends Controller
{
    public function __construct(
        private GoCardlessBankingService $gcService,
        private BankSyncService $syncService,
    ) {}

    /**
     * List available banking institutions.
     */
    public function institutions()
    {
        $institutions = $this->gcService->getInstitutions();

        return response()->json(['data' => $institutions]);
    }

    /**
     * List user's bank connections.
     */
    public function index(Request $request)
    {
        $connections = BankConnection::where('user_id', $request->user()->id)
            ->with('accounts')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'provider' => $c->provider,
                'status' => $c->status,
                'institution_id' => $c->institution_id,
                'institution_name' => $c->institution_name,
                'institution_logo' => $c->institution_logo,
                'last_synced_at' => $c->last_synced_at,
                'consent_expires_at' => $c->consent_expires_at,
                'accounts_count' => $c->accounts->count(),
                'created_at' => $c->created_at,
            ]);

        return response()->json(['data' => $connections]);
    }

    /**
     * Create a new bank connection: agreement + requisition → redirect link.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|string',
        ]);

        $institutionId = $validated['institution_id'];

        // Find institution name/logo from cached list
        $institutions = $this->gcService->getInstitutions();
        $institution = collect($institutions)->firstWhere('id', $institutionId);
        $institutionName = $institution['name'] ?? $institutionId;
        $institutionLogo = $institution['logo'] ?? null;

        // Create end-user agreement
        $agreement = $this->gcService->createAgreement($institutionId);

        // Generate unique reference for callback lookup
        $reference = 'user_' . $request->user()->id . '_' . Str::random(12);

        // Create requisition with redirect
        $redirectUrl = url('/finance/banking/callback');
        $requisition = $this->gcService->createRequisition(
            $institutionId,
            $redirectUrl,
            $agreement['id'],
            $reference,
        );

        // Calculate consent expiry
        $accessDays = $agreement['access_valid_for_days'] ?? 90;
        $consentExpires = now()->addDays($accessDays);

        // Save bank connection
        $connection = BankConnection::create([
            'user_id' => $request->user()->id,
            'provider' => 'gocardless',
            'status' => 'pending',
            'institution_id' => $institutionId,
            'institution_name' => $institutionName,
            'institution_logo' => $institutionLogo,
            'requisition_id' => $requisition['id'],
            'agreement_id' => $agreement['id'],
            'reference' => $reference,
            'consent_expires_at' => $consentExpires,
        ]);

        return response()->json([
            'data' => [
                'id' => $connection->id,
                'status' => $connection->status,
                'link' => $requisition['link'],
            ],
        ], 201);
    }

    /**
     * Show a specific bank connection.
     */
    public function show(Request $request, BankConnection $connection)
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        return response()->json([
            'data' => [
                'id' => $connection->id,
                'provider' => $connection->provider,
                'status' => $connection->status,
                'institution_name' => $connection->institution_name,
                'institution_logo' => $connection->institution_logo,
                'last_synced_at' => $connection->last_synced_at,
                'consent_expires_at' => $connection->consent_expires_at,
                'accounts' => $connection->accounts,
            ],
        ]);
    }

    /**
     * Delete a bank connection and revoke on GoCardless.
     */
    public function destroy(Request $request, BankConnection $connection)
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        // Try to clean up on GoCardless
        if ($connection->requisition_id) {
            try {
                $this->gcService->deleteRequisition($connection->requisition_id);
            } catch (\Exception $e) {
                // Non-critical: proceed with local deletion
            }
        }

        $connection->update(['status' => 'revoked']);
        $connection->delete();

        return response()->json(['message' => 'Conexión bancaria eliminada']);
    }

    /**
     * Handle redirect callback after user authenticates at their bank.
     * This is a web route (session auth), not an API route.
     */
    public function callback(Request $request)
    {
        $ref = $request->get('ref');

        if (!$ref) {
            return redirect('/finance/accounts?banking_error=missing_reference');
        }

        $connection = BankConnection::where('reference', $ref)->first();

        if (!$connection) {
            return redirect('/finance/accounts?banking_error=connection_not_found');
        }

        try {
            // Check requisition status on GoCardless
            $requisition = $this->gcService->getRequisition($connection->requisition_id);
            $status = $this->gcService->mapRequisitionStatus($requisition['status'] ?? '');

            $connection->update(['status' => $status]);

            if ($status === 'active') {
                $this->syncService->fullSync($connection);
            }

            return redirect('/finance/accounts?banking_success=true');
        } catch (\Exception $e) {
            return redirect('/finance/accounts?banking_error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Manually trigger sync for a connection.
     */
    public function sync(Request $request, BankConnection $connection)
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        if (!$connection->isActive()) {
            return response()->json([
                'error' => 'La conexión no está activa o el consentimiento ha expirado.',
            ], 422);
        }

        $result = $this->syncService->fullSync($connection);

        return response()->json(['data' => $result]);
    }

    /**
     * Reconnect an expired connection (create new agreement+requisition for same institution).
     */
    public function reconnect(Request $request, BankConnection $connection)
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        $institutionId = $connection->institution_id;

        $agreement = $this->gcService->createAgreement($institutionId);

        $reference = 'user_' . $request->user()->id . '_' . Str::random(12);
        $redirectUrl = url('/finance/banking/callback');

        $requisition = $this->gcService->createRequisition(
            $institutionId,
            $redirectUrl,
            $agreement['id'],
            $reference,
        );

        $accessDays = $agreement['access_valid_for_days'] ?? 90;

        $connection->update([
            'status' => 'pending',
            'requisition_id' => $requisition['id'],
            'agreement_id' => $agreement['id'],
            'reference' => $reference,
            'consent_expires_at' => now()->addDays($accessDays),
        ]);

        return response()->json([
            'data' => [
                'id' => $connection->id,
                'link' => $requisition['link'],
            ],
        ]);
    }
}
