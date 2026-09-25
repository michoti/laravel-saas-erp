<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // Distinct from invoice_number: the receipt is what's handed to
            // the customer and may be reprinted; receipt_number is assigned
            // once, at first print, so reprints stay identifiable/unique
            // without colliding with the accounting invoice sequence.
            $table->string('receipt_number')->nullable()->unique()->after('invoice_number');
            $table->timestampTz('printed_at')->nullable()->after('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['receipt_number', 'printed_at']);
        });
    }
};
