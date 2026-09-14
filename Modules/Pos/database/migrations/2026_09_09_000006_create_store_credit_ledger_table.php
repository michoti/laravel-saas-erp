<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only, mirroring stock_ledger's design: a customer's store
        // credit balance is always the SUM of this table, never a mutable
        // counter mutated directly (the denormalized `customers.
        // store_credit_balance` column is a cache of that sum, refreshed
        // atomically alongside each insert — see RefundService).
        Schema::create('store_credit_ledger', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();

            $table->decimal('amount', 12, 2); // positive = credit issued, negative = redeemed at checkout
            $table->string('reference_type')->nullable(); // e.g. Modules\Pos\Models\Refund, Modules\Pos\Models\Order
            $table->uuid('reference_id')->nullable();
            $table->text('note')->nullable();

            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_credit_ledger');
    }
};
