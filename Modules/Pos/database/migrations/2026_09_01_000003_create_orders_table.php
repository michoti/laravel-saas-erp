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
        // Server-authoritative, gap-tolerant sequence for official invoice
        // numbers. Never generated on the client. A Postgres SEQUENCE
        // guarantees monotonic, collision-free allocation even under heavy
        // concurrent sync-batch processing across Horizon workers.
        DB::statement('CREATE SEQUENCE IF NOT EXISTS pos_invoice_number_seq START 1000');

        DB::statement(<<<'SQL'
            CREATE TYPE order_status AS ENUM (
                'draft', 'awaiting_payment', 'paid', 'partially_refunded', 'refunded', 'voided'
            )
        SQL);

        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary(); // UUIDv7, client-generated offline

            // Client-side temporary human-readable tracker (e.g. "REG1-0042").
            // Never authoritative, never shown on a printed receipt once the
            // real invoice_number has been assigned.
            $table->string('local_reference')->nullable()->index();

            // NULL until assigned server-side during sync-batch processing.
            $table->string('invoice_number')->nullable()->unique();

            $table->uuid('device_id')->index();
            $table->uuid('customer_id')->nullable()->index();
            $table->uuid('cashier_user_id')->nullable()->index();
            $table->uuid('warehouse_id')->nullable()->index();

            $table->addColumn('order_status', 'status')->default('draft');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->string('currency', 3)->default('KES');

            $table->timestampTz('order_date'); // business time, device clock

            // --- Offline-first / sync metadata ---
            $table->timestampTz('client_created_at')->nullable();
            $table->timestampTz('client_updated_at')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->unsignedBigInteger('version')->default(1);

            $table->timestampsTz();

            $table->index(['status']);
            $table->index(['order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        DB::statement('DROP TYPE IF EXISTS order_status');
        DB::statement('DROP SEQUENCE IF EXISTS pos_invoice_number_seq');
    }
};
