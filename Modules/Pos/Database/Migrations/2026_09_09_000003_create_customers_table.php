<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();

            // Denormalized running balance for instant POS-terminal lookup
            // at checkout ("apply store credit?"); the append-only
            // store_credit_ledger table below remains the source of truth
            // and this column is only ever updated inside the same
            // transaction that writes a ledger row (see RefundService).
            $table->decimal('store_credit_balance', 12, 2)->default(0);

            $table->timestampTz('client_created_at')->nullable();
            $table->timestampTz('client_updated_at')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->unsignedBigInteger('version')->default(1);

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
