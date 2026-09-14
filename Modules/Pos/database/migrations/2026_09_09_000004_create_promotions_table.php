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

        Schema::create('promotions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');

            $table->enum('discount_type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->decimal('discount_value', 10, 2); // percentage (0-100) or a fixed KES amount, per discount_type

            // NULL = storewide. Set to scope the promotion to one product;
            // a category-level or price-list-level promotion is a natural
            // future extension of this same table.
            $table->uuid('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();

            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('is_active')->default(true);

            $table->timestampsTz();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
        DB::statement('DROP TYPE IF EXISTS promotion_discount_type');
    }
};
