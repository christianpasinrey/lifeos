<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Services\AccountService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private AccountService $service) {}

    public function index(Request $request)
    {
        $accounts = $this->service->getAccounts(
            $request->user(),
            $request->boolean('active_only', true)
        );

        return response()->json(['data' => $accounts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,cash,credit_card,savings,investment,other',
            'currency' => 'sometimes|string|size:3',
            'initial_balance' => 'sometimes|numeric',
            'color' => 'sometimes|string|max:7',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
        ]);

        $account = $this->service->createAccount($request->user(), $validated);

        return response()->json(['data' => $account], 201);
    }

    public function show(Request $request, Account $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        return response()->json(['data' => $account]);
    }

    public function update(Request $request, Account $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:bank,cash,credit_card,savings,investment,other',
            'currency' => 'sometimes|string|size:3',
            'initial_balance' => 'sometimes|numeric',
            'color' => 'sometimes|string|max:7',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        $updated = $this->service->updateAccount($account, $validated);

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, Account $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        $this->service->deleteAccount($account);

        return response()->json(['message' => 'Cuenta eliminada']);
    }

    public function recalculate(Request $request, Account $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        $updated = $this->service->recalculateBalance($account);

        return response()->json(['data' => $updated]);
    }
}
