<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\TransactionService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(private TransactionService $service) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|exists:finance_accounts,id',
            'to_account_id' => 'required|exists:finance_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        // Verify both accounts belong to user
        $user = $request->user();
        abort_unless(
            $user->financeAccounts()->where('id', $validated['from_account_id'])->exists(),
            403,
            'La cuenta de origen no pertenece al usuario.'
        );
        abort_unless(
            $user->financeAccounts()->where('id', $validated['to_account_id'])->exists(),
            403,
            'La cuenta de destino no pertenece al usuario.'
        );

        $transfer = $this->service->createTransfer($user, $validated);

        return response()->json(['data' => $transfer], 201);
    }
}
