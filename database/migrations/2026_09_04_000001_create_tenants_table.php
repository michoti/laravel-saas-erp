<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary(); // stancl/tenancy tenant id, also the tenant DB suffix

            $table->string('name')->index();

            // Number InitiateSubscriptionStkPushJob sends the renewal STK Push to.
            // Deliberately just a phone number, never a stored payment token.
            $table->string('billing_phone')->nullable();

            // { primary_color, logo_url, font_family, custom_css } — injected by Filament at runtime.
            $table->jsonb('theme')->nullable();

            // stancl/tenancy free-form tenant data column.
            $table->jsonb('data')->nullable();

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenants');
    }
};