<?php

namespace App\Modules\Finance\Tools;

use App\Models\User;
use App\Modules\Finance\Services\AccountService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetAccounts implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene las cuentas financieras del usuario con sus saldos actuales.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'include_inactive' => $schema->boolean()
                ->description('Si es true, incluye también las cuentas inactivas (opcional, por defecto: false)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(AccountService::class);
        $activeOnly = empty($request['include_inactive']);
        $accounts = $service->getAccounts($this->user, $activeOnly);

        if ($accounts->isEmpty()) {
            return 'No se encontraron cuentas financieras.';
        }

        $totalBalance = $service->getTotalBalance($this->user);

        $output = "🏦 **Cuentas financieras** ({$accounts->count()}):\n\n";

        foreach ($accounts as $account) {
            $status = $account->is_active ? '' : ' [Inactiva]';
            $output .= "• **{$account->name}**{$status} ({$account->type} - {$account->currency})\n";
            $output .= "  Saldo: €" . number_format($account->current_balance, 2) . "\n\n";
        }

        $output .= "💰 **Patrimonio total:** €" . number_format($totalBalance, 2) . "\n";

        return $output;
    }
}
