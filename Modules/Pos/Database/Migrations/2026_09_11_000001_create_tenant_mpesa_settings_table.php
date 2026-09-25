<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lives in the TENANT database, not central — a tenant's own
        // Daraja credentials (their own Till/PayBill, receiving their own
        // customers' payments) are exactly the kind of "sensitive M-Pesa
        // transaction" data that must never be reachable from the central
        // database or from another tenant's connection. Storing this
        // table here, rather than as encrypted columns on the central
        // `tenants` table, means even a fully compromised central
        // database credential leak exposes zero tenant M-Pesa secrets —
        // each one only ever lives inside that one tenant's own isolated
        // Postgres database.
        Schema::create('tenant_mpesa_settings', function (Blueprint $table): void {
            $table->id();

            // Encrypted at rest via the model's `encrypted` casts — see
            // Modules\Pos\Models\TenantMpesaSetting. Nullable: a tenant
            // that hasn't configured M-Pesa yet simply can't accept STK
            // Push payments (cash/card still work) until they do.
            $table->text('consumer_key')->nullable();
            $table->text('consumer_secret')->nullable();
            $table->text('shortcode')->nullable();
            $table->text('passkey')->nullable();

            $table->string('env')->default('sandbox'); // 'sandbox' or 'production'
            $table->string('transaction_type')->default('paybill'); // 'paybill' or 'till'

            $table->timestampTz('verified_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_mpesa_settings');
    }
};
