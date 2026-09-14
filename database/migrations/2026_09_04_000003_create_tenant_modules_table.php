<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenant_modules', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('module_key'); // e.g. 'pos', 'invoicing', 'crm'

            $table->timestampTz('enabled_at')->nullable();
            $table->timestampTz('disabled_at')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->unique(['tenant_id', 'module_key']);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_modules');
    }
};
