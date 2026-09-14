<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // Nullable + unique-when-present: not every product is barcoded
            // (e.g. bulk/manual-entry items), but a scanned code must always
            // resolve to exactly one product for checkout to be instant.
            $table->string('barcode')->nullable()->unique()->after('sku');
            $table->string('image_path')->nullable()->after('barcode');
            $table->string('category')->nullable()->index()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['barcode', 'image_path', 'category']);
        });
    }
};
