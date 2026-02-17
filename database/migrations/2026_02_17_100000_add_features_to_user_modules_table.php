<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_modules', function (Blueprint $table) {
            $table->json('features')->nullable()->after('limits');
        });
    }

    public function down(): void
    {
        Schema::table('user_modules', function (Blueprint $table) {
            $table->dropColumn('features');
        });
    }
};
