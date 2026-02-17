<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Tax;
use App\Modules\Finance\Models\TaxRate;
use App\Modules\Finance\Services\TaxService;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function __construct(private TaxService $service) {}

    public function index(Request $request)
    {
        $taxes = $this->service->getTaxesForUser($request->user()->id);

        return response()->json(['data' => $taxes]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:applicable,deductible',
            'is_default' => 'sometimes|boolean',
        ]);

        $tax = $this->service->createTax($request->user()->id, $validated);

        return response()->json(['data' => $tax->load('rates')], 201);
    }

    public function update(Request $request, Tax $tax)
    {
        abort_unless(
            $tax->user_id === $request->user()->id,
            403,
            'No se puede editar impuestos del sistema.'
        );

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:applicable,deductible',
            'is_default' => 'sometimes|boolean',
        ]);

        $updated = $this->service->updateTax($tax, $validated);

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, Tax $tax)
    {
        abort_unless(
            $tax->user_id === $request->user()->id,
            403,
            'No se puede eliminar impuestos del sistema.'
        );

        $this->service->deleteTax($tax);

        return response()->json(['message' => 'Impuesto eliminado']);
    }

    public function storeRate(Request $request, Tax $tax)
    {
        // Allow adding rates to system taxes too (user customization)
        abort_unless(
            $tax->user_id === $request->user()->id || $tax->user_id === null,
            403
        );

        // Only the owner can add rates to user taxes; system taxes need a user-owned copy
        if ($tax->user_id !== null) {
            abort_unless($tax->user_id === $request->user()->id, 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'is_default' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        $rate = $this->service->createRate($tax, $validated);

        return response()->json(['data' => $rate], 201);
    }

    public function updateRate(Request $request, TaxRate $taxRate)
    {
        $tax = $taxRate->tax;
        abort_unless(
            $tax->user_id === $request->user()->id,
            403,
            'No se pueden editar tasas del sistema.'
        );

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'rate' => 'sometimes|numeric|min:0|max:100',
            'is_default' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        $updated = $this->service->updateRate($taxRate, $validated);

        return response()->json(['data' => $updated]);
    }

    public function destroyRate(Request $request, TaxRate $taxRate)
    {
        $tax = $taxRate->tax;
        abort_unless(
            $tax->user_id === $request->user()->id,
            403,
            'No se pueden eliminar tasas del sistema.'
        );

        $this->service->deleteRate($taxRate);

        return response()->json(['message' => 'Tasa eliminada']);
    }
}
