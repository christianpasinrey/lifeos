<?php

namespace App\Modules\Finance;

use App\Models\User;
use App\Modules\Ai\Contracts\AiSpecialization;
use App\Modules\Finance\Tools\CreateTransaction;
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
        ## Capacidades de Finanzas Personales
        
        Eres un experto en economía y finanzas personales. Puedes ayudar al usuario a:
        - Ver su resumen financiero (ingresos, gastos, balance)
        - Consultar transacciones específicas por período o categoría
        - Registrar nuevos ingresos y gastos
        - Analizar patrones de gasto y sugerir áreas de ahorro
        - Dar consejos sobre presupuesto y planificación financiera
        
        Tu objetivo es ayudar al usuario a tomar decisiones financieras inteligentes y alcanzar estabilidad económica.
        Cuando analices las finanzas, sé específico con números y porcentajes. Ofrece recomendaciones prácticas y realistas.
        INSTRUCTIONS;
    }

    public function tools(User $user): array
    {
        return [
            new GetFinanceSummary($user),
            new GetTransactions($user),
            new CreateTransaction($user),
        ];
    }
}
