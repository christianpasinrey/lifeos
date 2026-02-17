<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->date('streak_freeze_used_at')->nullable()->after('best_streak');
            $table->unsignedInteger('streak_freeze_count')->default(0)->after('streak_freeze_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn(['streak_freeze_used_at', 'streak_freeze_count']);
        });
    }
};
