<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Tax;
use App\Modules\Finance\Models\TaxRate;

class TaxService
{
    public function getTaxesForUser(int $userId)
    {
        return Tax::forUser($userId)->with('rates')->get();
    }

    public function createTax(int $userId, array $data): Tax
    {
        $data['user_id'] = $userId;
        return Tax::create($data);
    }

    public function updateTax(Tax $tax, array $data): Tax
    {
        $tax->update($data);
        return $tax->fresh('rates');
    }

    public function deleteTax(Tax $tax): void
    {
        $tax->delete();
    }

    public function createRate(Tax $tax, array $data): TaxRate
    {
        $data['tax_id'] = $tax->id;
        return TaxRate::create($data);
    }

    public function updateRate(TaxRate $rate, array $data): TaxRate
    {
        $rate->update($data);
        return $rate->fresh();
    }

    public function deleteRate(TaxRate $rate): void
    {
        $rate->delete();
    }

    /**
     * Snapshot tax data for storing on a line tax (denormalized for historical accuracy).
     */
    public function snapshotTaxData(int $taxId, int $taxRateId): array
    {
        $tax = Tax::findOrFail($taxId);
        $rate = TaxRate::findOrFail($taxRateId);

        return [
            'tax_id' => $tax->id,
            'tax_rate_id' => $rate->id,
            'tax_name' => $tax->name,
            'tax_type' => $tax->type,
            'rate' => $rate->rate,
        ];
    }
}
