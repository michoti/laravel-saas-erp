<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenant_usage_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->date('snapshot_date');

            $table->unsignedInteger('order_count')->default(0);
            $table->decimal('gross_sales', 16, 2)->default(0);
            $table->unsignedInteger('active_user_count')->default(0);
            $table->unsignedInteger('product_count')->default(0);
            $table->unsignedInteger('low_stock_count')->default(0);

            $table->foreign('tenant_id')->references('id')->on('tenants')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->unique(['tenant_id', 'snapshot_date']);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_usage_snapshots');
    }
};
