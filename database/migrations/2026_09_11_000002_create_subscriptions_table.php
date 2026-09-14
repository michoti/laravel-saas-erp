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
        DB::connection('central')->statement(<<<'SQL'
            CREATE TYPE subscription_status AS ENUM (
                'trialing', 'active', 'past_due', 'canceled', 'unpaid'
            )
        SQL);

        Schema::connection('central')->create('subscriptions', function (Blueprint $table): void {
            $table->id();

            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();

            $table->addColumn('subscription_status', 'status')->default('trialing');

            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('current_period_start');
            $table->timestampTz('current_period_end');
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestampTz('canceled_at')->nullable();

            $table->timestampsTz();

            // One active-or-trialing subscription per tenant at a time —
            // plan changes go through SubscriptionService::swap(), which
            // updates this same row rather than creating a second one.
            $table->unique('tenant_id');
            $table->index(['status', 'current_period_end']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('subscriptions');
        DB::connection('central')->statement('DROP TYPE IF EXISTS subscription_status');
    }
};
