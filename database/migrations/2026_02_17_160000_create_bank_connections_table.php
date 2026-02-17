<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('provider', ['ing'])->default('ing');
            $table->text('client_id');
            $table->enum('status', ['pending', 'active', 'expired', 'revoked'])->default('pending');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('consent_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_connection_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_connection_id')->constrained()->cascadeOnDelete();
            $table->text('tls_certificate');
            $table->text('tls_key');
            $table->text('signing_certificate');
            $table->text('signing_key');
            $table->timestamps();
        });

        // Add bank connection fields to finance_accounts
        if (!Schema::hasColumn('finance_accounts', 'bank_connection_id')) {
            Schema::table('finance_accounts', function (Blueprint $table) {
                $table->foreignId('bank_connection_id')->nullable()->after('sort_order')
                    ->constrained('bank_connections')->nullOnDelete();
                $table->string('external_account_id', 255)->nullable()->after('bank_connection_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('finance_accounts', 'bank_connection_id')) {
            Schema::table('finance_accounts', function (Blueprint $table) {
                $table->dropForeign(['bank_connection_id']);
                $table->dropColumn(['bank_connection_id', 'external_account_id']);
            });
        }

        Schema::dropIfExists('bank_connection_certificates');
        Schema::dropIfExists('bank_connections');
    }
};
