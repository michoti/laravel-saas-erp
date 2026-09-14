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
            $table->string('id')->primary(); // stancl/tenancy UUID tenant id, also the tenant DB suffix

            $table->string('name');

            // The phone number InitiateSubscriptionStkPushJob sends the
            // renewal STK Push to. Deliberately just a phone number, not
            // a stored card/payment-method token — there's nothing here
            // for a database breach to expose beyond what's already
            // public information about the tenant.
            $table->string('billing_phone')->nullable();

            // Frontend customization — injected by Filament at runtime.
            $table->json('theme')->nullable(); // { primary_color, logo_url, font_family, custom_css }

            $table->json('data')->nullable(); // stancl/tenancy free-form tenant data column

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenants');
    }
};
