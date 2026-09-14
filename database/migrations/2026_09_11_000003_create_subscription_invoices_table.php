<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::connection('central')->create('subscription_invoices', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();

            // Denormalized for fast lookup/reporting without a join —
            // this table is central-only and never crosses into any
            // tenant's own database, so this is purely a reporting
            // convenience, not a privacy concern.
            $table->string('tenant_id');

            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->timestampTz('due_date');

            $table->enum('subscription_invoice_status', ['pending', 'awaiting_confirmation', 'paid', 'failed', 'void'])->default('pending');

            // Same idempotency mechanism as the POS `payments` table's
            // M-Pesa columns (see Modules/Pos/Database/Migrations/
            // ..._create_payments_table.php) — deliberately mirrored,
            // never shared, since this is billing data in the CENTRAL
            // database and that is tenant POS sales data in a TENANT
            // database. Two structurally-identical but physically and
            // logically separate idempotency mechanisms, not one shared
            // table doing double duty across a privacy boundary.
            $table->string('checkout_request_id')->nullable()->unique();
            $table->string('merchant_request_id')->nullable();
            $table->string('mpesa_receipt_number')->nullable()->unique();
            $table->string('transaction_hash')->nullable()->unique();
            $table->string('payer_phone')->nullable();
            $table->timestampTz('stk_pushed_at')->nullable();

            $table->timestampTz('paid_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestampsTz();

            $table->index(['subscription_invoice_status', 'due_date']);
            $table->index(['tenant_id']);
            // Composite, not two separate single-column indexes: the
            // reconciliation sweep (see ReconcileSubscriptionPaymentsCommand)
            // filters on status + stk_pushed_at together, on a schedule,
            // for the lifetime of the table — the same query shape flagged
            // as under-indexed on the POS `payments` table in the prior
            // performance review. Fixed here from the start instead of
            // waiting to discover it again at scale.
            $table->index(['subscription_invoice_status', 'stk_pushed_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('subscription_invoices');
        DB::connection('central')->statement('DROP TYPE IF EXISTS subscription_invoice_status');
    }
};
