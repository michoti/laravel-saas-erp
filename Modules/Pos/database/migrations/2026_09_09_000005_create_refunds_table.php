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

        Schema::create('refunds', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->uuid('order_item_id')->nullable(); // null = whole-order refund
            $table->foreign('order_item_id')->references('id')->on('order_items')->nullOnDelete();

            $table->decimal('amount', 14, 2);
            $table->enum('refund_method', ['cash', 'mpesa_manual', 'store_credit'])->default('cash');
            $table->text('reason')->nullable();

            $table->uuid('processed_by_user_id');

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        DB::statement('DROP TYPE IF EXISTS refund_method');
    }
};
