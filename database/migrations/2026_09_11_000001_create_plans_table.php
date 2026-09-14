<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->unsignedSmallInteger('billing_interval_days')->default(30);

            $table->unsignedSmallInteger('trial_days')->default(14);
            $table->json('module_keys')->nullable(); // which modules a subscription to this plan unlocks

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('plans');
    }
};
