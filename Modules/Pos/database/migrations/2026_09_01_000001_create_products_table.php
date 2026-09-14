<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->uuid('id')->primary(); // UUIDv7, client- or server-generated

            $table->string('sku')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('category_id')->nullable()->index();

            $table->decimal('unit_price', 14, 2);
            $table->decimal('tax_rate', 6, 4)->default(0); // e.g. 0.1600 = 16% VAT
            $table->string('currency', 3)->default('KES');

            $table->boolean('is_active')->default(true);
            $table->boolean('track_inventory')->default(true);

            // --- Offline-first / sync metadata (required on every POS table) ---
            $table->timestampTz('client_created_at')->nullable();
            $table->timestampTz('client_updated_at')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->unsignedBigInteger('version')->default(1);
            // Device that last wrote this row locally — useful for LWW audit trail
            $table->uuid('last_writer_device_id')->nullable();

            $table->timestampsTz(); // server-authoritative created_at / updated_at
            $table->softDeletesTz();

            $table->unique(['sku']);
            $table->index(['is_active']);
            $table->index(['version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
