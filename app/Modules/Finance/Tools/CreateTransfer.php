<?php

namespace App\Modules\Finance\Tools;

use App\Models\User;
use App\Modules\Finance\Services\TransactionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTransfer implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Realiza una transferencia de dinero entre dos cuentas del usuario.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_account_id' => $schema->integer()
                ->description('ID de la cuenta origen')
                ->required(),
            'to_account_id' => $schema->integer()
                ->description('ID de la cuenta destino')
                ->required(),
            'amount' => $schema->number()
                ->description('Cantidad a transferir (debe ser mayor a 0)')
                ->required(),
            'description' => $schema->string()
                ->description('Descripción de la transferencia (opcional)'),
            'date' => $schema->string()
                ->description('Fecha de la transferencia en formato YYYY-MM-DD (opcional, por defecto: hoy)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(TransactionService::class);

        // Verify both accounts belong to user
        $fromAccount = $this->user->financeAccounts()->find($request['from_account_id']);
        $toAccount = $this->user->financeAccounts()->find($request['to_account_id']);

        if (!$fromAccount) {
            return '❌ La cuenta origen no existe o no pertenece al usuario.';
        }

        if (!$toAccount) {
            return '❌ La cuenta destino no existe o no pertenece al usuario.';
        }

        if ($fromAccount->id === $toAccount->id) {
            return '❌ La cuenta origen y destino no pueden ser la misma.';
        }

        $transfer = $service->createTransfer($this->user, [
            'from_account_id' => (int) $request['from_account_id'],
            'to_account_id' => (int) $request['to_account_id'],
            'amount' => (float) $request['amount'],
            'description' => $request['description'] ?? null,
            'date' => $request['date'] ?? null,
        ]);

        return "🔄 Transferencia realizada: €" . number_format($transfer->amount, 2) .
               " de '{$fromAccount->name}' a '{$toAccount->name}'" .
               " el {$transfer->date->format('Y-m-d')}";
    }
}
