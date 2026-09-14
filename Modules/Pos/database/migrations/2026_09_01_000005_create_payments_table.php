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
        DB::statement(<<<'SQL'
            CREATE TYPE payment_method AS ENUM ('cash', 'mpesa', 'card', 'other')
        SQL);

        DB::statement(<<<'SQL'
            CREATE TYPE payment_status AS ENUM (
                'pending', 'awaiting_confirmation', 'completed', 'failed', 'reversed'
            )
        SQL);

        Schema::create('payments', function (Blueprint $table): void {
            $table->uuid('id')->primary(); // UUIDv7, client-generated offline

            $table->uuid('order_id')->index();
            $table->foreign('order_id')->references('id')->on('orders')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->addColumn('payment_method', 'method');
            $table->addColumn('payment_status', 'status')->default('pending');

            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('KES');

            // --- M-Pesa STK push idempotency keys ---
            // checkout_request_id: assigned once STK push is initiated server-side.
            // transaction_hash: sha256(checkout_request_id + result_code + receipt),
            //   set only once, on first successful callback processing — the
            //   uniqueness constraint is what makes the callback handler
            //   provably idempotent against Safaricom's at-least-once delivery.
            $table->string('checkout_request_id')->nullable()->unique();
            $table->string('merchant_request_id')->nullable();
            $table->string('mpesa_receipt_number')->nullable()->unique();
            $table->string('transaction_hash')->nullable()->unique();
            $table->string('payer_phone')->nullable();

            $table->timestampTz('paid_at')->nullable();
            $table->text('failure_reason')->nullable();

            // --- Offline-first / sync metadata ---
            $table->timestampTz('client_created_at')->nullable();
            $table->timestampTz('client_updated_at')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->unsignedBigInteger('version')->default(1);

            $table->timestampsTz();

            $table->index(['status']);
            $table->index(['method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        DB::statement('DROP TYPE IF EXISTS payment_status');
        DB::statement('DROP TYPE IF EXISTS payment_method');
    }
};
