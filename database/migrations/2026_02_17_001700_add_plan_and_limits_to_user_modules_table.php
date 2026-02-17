<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $freeLimits = [
        'habits' => ['max_habits' => 5],
        'custom_entities' => ['max_entities' => 3],
        'ai_coach' => ['max_messages_per_day' => 10],
    ];

    public function up(): void
    {
        Schema::table('user_modules', function (Blueprint $table) {
            $table->string('plan')->default('free')->after('is_active');
            $table->json('limits')->nullable()->after('plan');
        });

        // Populate default free limits for existing records
        foreach ($this->freeLimits as $module => $limits) {
            DB::table('user_modules')
                ->where('module', $module)
                ->where('plan', 'free')
                ->update(['limits' => json_encode($limits)]);
        }
    }

    public function down(): void
    {
        Schema::table('user_modules', function (Blueprint $table) {
            $table->dropColumn(['plan', 'limits']);
        });
    }
};
