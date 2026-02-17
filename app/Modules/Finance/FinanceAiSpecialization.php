<?php

namespace App\Modules\Finance;

use App\Models\User;
use App\Modules\Ai\Contracts\AiSpecialization;
use App\Modules\Finance\Tools\CreateTransaction;
use App\Modules\Finance\Tools\CreateTransfer;
use App\Modules\Finance\Tools\GetAccounts;
use App\Modules\Finance\Tools\GetBudgetStatus;
use App\Modules\Finance\Tools\GetFinanceSummary;
use App\Modules\Finance\Tools\GetTransactions;

class FinanceAiSpecialization implements AiSpecialization
{
    public function moduleSlug(): string
    {
        return 'finance';
    }

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        ## Capacidades de Finanzas Personales (ERP)

        Eres un experto en economía y finanzas personales. Puedes ayudar al usuario a:
        - Ver su resumen financiero (ingresos, gastos, balance, patrimonio total)
        - Consultar transacciones por período, categoría o cuenta
        - Registrar nuevos ingresos y gastos (opcionalmente con cuenta destino)
        - Ver todas sus cuentas financieras con saldos actuales
        - Realizar transferencias entre cuentas
        - Consultar el estado de sus presupuestos (límite, gasto, porcentaje)
        - Analizar patrones de gasto y sugerir áreas de ahorro
        - Dar consejos sobre presupuesto y planificación financiera

        El usuario puede tener múltiples cuentas (banco, efectivo, tarjeta, ahorro, inversión).
        Los presupuestos tienen período (mensual, trimestral, anual) y pueden estar vinculados a categorías.

        Tu objetivo es ayudar al usuario a tomar decisiones financieras inteligentes y alcanzar estabilidad económica.
        Cuando analices las finanzas, sé específico con números y porcentajes. Ofrece recomendaciones prácticas y realistas.
        Si un presupuesto está por encima del 80%, advierte al usuario proactivamente.
        INSTRUCTIONS;
    }

    public function tools(User $user): array
    {
        return [
            new GetFinanceSummary($user),
            new GetTransactions($user),
            new CreateTransaction($user),
            new GetAccounts($user),
            new CreateTransfer($user),
            new GetBudgetStatus($user),
        ];
    }
}
