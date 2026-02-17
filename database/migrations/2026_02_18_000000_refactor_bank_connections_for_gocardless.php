<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Revoke any existing ING connections (no longer compatible)
        DB::table('bank_connections')->update(['status' => 'revoked']);

        // Drop certificates table (no longer needed with GoCardless)
        Schema::dropIfExists('bank_connection_certificates');

        Schema::table('bank_connections', function (Blueprint $table) {
            // Drop ING-specific columns
            if (Schema::hasColumn('bank_connections', 'client_id')) {
                $table->dropColumn('client_id');
            }
            if (Schema::hasColumn('bank_connections', 'access_token')) {
                $table->dropColumn('access_token');
            }
            if (Schema::hasColumn('bank_connections', 'refresh_token')) {
                $table->dropColumn('refresh_token');
            }
            if (Schema::hasColumn('bank_connections', 'token_expires_at')) {
                $table->dropColumn('token_expires_at');
            }
        });

        Schema::table('bank_connections', function (Blueprint $table) {
            // Change provider to string (flexible for future providers)
            $table->string('provider', 50)->default('gocardless')->change();

            // Add GoCardless-specific columns
            $table->string('requisition_id', 255)->nullable()->after('provider');
            $table->string('agreement_id', 255)->nullable()->after('requisition_id');
            $table->string('institution_id', 255)->nullable()->after('agreement_id');
            $table->string('institution_name', 255)->nullable()->after('institution_id');
            $table->text('institution_logo')->nullable()->after('institution_name');
            $table->string('reference', 255)->nullable()->after('institution_logo');
        });

        // Update status enum to include 'suspended'
        DB::statement("ALTER TABLE bank_connections MODIFY status ENUM('pending','active','expired','revoked','suspended') DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->dropColumn([
                'requisition_id',
                'agreement_id',
                'institution_id',
                'institution_name',
                'institution_logo',
                'reference',
            ]);
        });

        Schema::table('bank_connections', function (Blueprint $table) {
            $table->text('client_id')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
        });

        DB::statement("ALTER TABLE bank_connections MODIFY status ENUM('pending','active','expired','revoked') DEFAULT 'pending'");
        DB::statement("ALTER TABLE bank_connections MODIFY provider ENUM('ing') DEFAULT 'ing'");

        Schema::create('bank_connection_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_connection_id')->constrained()->cascadeOnDelete();
            $table->text('tls_certificate');
            $table->text('tls_key');
            $table->text('signing_certificate');
            $table->text('signing_key');
            $table->timestamps();
        });
    }
};
