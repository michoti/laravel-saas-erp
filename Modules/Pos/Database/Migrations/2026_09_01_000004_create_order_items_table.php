<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->uuid('id')->primary(); // UUIDv7, client-generated offline

            $table->uuid('order_id')->index();
            $table->foreign('order_id')->references('id')->on('orders')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->uuid('product_id')->index();
            $table->foreign('product_id')->references('id')->on('products')
                ->cascadeOnUpdate()->restrictOnDelete();

            // Denormalized snapshot at time of sale — protects historical
            // invoices from later product price/name edits.
            $table->string('product_name_snapshot');
            $table->string('product_sku_snapshot');

            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('tax_rate', 6, 4)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('line_subtotal', 14, 2);
            $table->decimal('line_total', 14, 2);

            // --- Offline-first / sync metadata ---
            $table->timestampTz('client_created_at')->nullable();
            $table->timestampTz('client_updated_at')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->unsignedBigInteger('version')->default(1);

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
