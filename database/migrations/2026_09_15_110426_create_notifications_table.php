<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->jsonb('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Matches the panel's own query shape: "unread notifications
            // for this notifiable, newest first".
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });

        // GIN index over the jsonb `data` column so queries can filter on
        // arbitrary notification metadata (e.g. data->>'product_id',
        // data->>'severity') without a full table scan as this grows.
        DB::statement('CREATE INDEX notifications_data_gin_index ON notifications USING GIN (data)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};