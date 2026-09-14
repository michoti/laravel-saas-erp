<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            // Drives ReconcilePendingMpesaPaymentsCommand: a payment still
            // "awaiting_confirmation" this long after the STK push was
            // sent is a candidate for an active STK Query reconciliation
            // check, rather than waiting indefinitely on a callback that
            // may never arrive.
            $table->timestampTz('stk_pushed_at')->nullable()->after('checkout_request_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            // Composite, not relying on the separate single-column
            // `status`/`method` indexes already on this table: the
            // reconciliation sweep filters on method + status +
            // stk_pushed_at together, every minute, per tenant — flagged
            // as under-indexed in the prior performance review (two
            // single-column indexes force Postgres into a bitmap AND
            // rather than one efficient index scan). Fixed here, in the
            // same migration that introduces the column this index needs.
            $table->index(['method', 'status', 'stk_pushed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('stk_pushed_at');
        });
    }
};
