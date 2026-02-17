<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // IVA (applicable tax)
        $ivaId = DB::table('finance_taxes')->insertGetId([
            'user_id' => null,
            'name' => 'IVA',
            'type' => 'applicable',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('finance_tax_rates')->insert([
            [
                'tax_id' => $ivaId,
                'name' => 'General',
                'rate' => 21.00,
                'is_default' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tax_id' => $ivaId,
                'name' => 'Reducido',
                'rate' => 10.00,
                'is_default' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tax_id' => $ivaId,
                'name' => 'Superreducido',
                'rate' => 4.00,
                'is_default' => false,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // IRPF (deductible tax)
        $irpfId = DB::table('finance_taxes')->insertGetId([
            'user_id' => null,
            'name' => 'IRPF',
            'type' => 'deductible',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('finance_tax_rates')->insert([
            [
                'tax_id' => $irpfId,
                'name' => 'General',
                'rate' => 15.00,
                'is_default' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tax_id' => $irpfId,
                'name' => 'Nuevo autónomo',
                'rate' => 7.00,
                'is_default' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('finance_tax_rates')->whereIn('tax_id', function ($query) {
            $query->select('id')->from('finance_taxes')->whereNull('user_id');
        })->delete();

        DB::table('finance_taxes')->whereNull('user_id')->delete();
    }
};
