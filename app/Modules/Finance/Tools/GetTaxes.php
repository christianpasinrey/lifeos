<?php

namespace App\Modules\Finance\Tools;

use App\Models\User;
use App\Modules\Finance\Services\TaxService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTaxes implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene los impuestos disponibles (IVA, IRPF, etc.) con sus tipos impositivos e IDs para usar al crear líneas de transacción.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(TaxService::class);
        $taxes = $service->getTaxesForUser($this->user->id);

        if ($taxes->isEmpty()) {
            return 'No hay impuestos configurados. Crea impuestos primero desde la sección de finanzas.';
        }

        $output = "📋 **Impuestos disponibles** ({$taxes->count()}):\n\n";

        foreach ($taxes as $tax) {
            $type = $tax->type === 'applicable' ? 'Impuesto (suma al total)' : 'Retención (resta del total)';
            $output .= "• **{$tax->name}** (tax_id: {$tax->id}) — {$type}\n";

            if ($tax->rates->isNotEmpty()) {
                foreach ($tax->rates as $rate) {
                    $default = $rate->is_default ? ' [por defecto]' : '';
                    $output .= "  - {$rate->name}: {$rate->rate}% (tax_rate_id: {$rate->id}){$default}\n";
                }
            } else {
                $output .= "  - Sin tipos impositivos configurados\n";
            }

            $output .= "\n";
        }

        $output .= "Usa tax_id y tax_rate_id al crear líneas de transacción con impuestos.";

        return $output;
    }
}
