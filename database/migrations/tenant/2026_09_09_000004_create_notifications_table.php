<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The `database` notification channel's own table, INSIDE each tenant
 * database — tenant users are stored per-tenant, so their notifications
 * table has to be too (this is separate from the central `notifications`
 * table used by PlatformAdminUser's ->databaseNotifications() panel).
 *
 * `notifiable_id` is uuid (uuidMorphs, not morphs) to match this tenant
 * database's own `users.id`.
 *
 * A GIN index over the jsonb `data` column lets queries filter on arbitrary
 * notification metadata (e.g. `data->>'product_id'`, `data->>'severity'`)
 * without a full table scan as this table grows — see
 * App\Notifications\LowStockAlert for the shape of data actually stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->jsonb('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();

            // Covers the Filament notifications panel's own query shape:
            // "unread notifications for this user, newest first".
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });

        DB::statement('CREATE INDEX notifications_data_gin_index ON notifications USING GIN (data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
