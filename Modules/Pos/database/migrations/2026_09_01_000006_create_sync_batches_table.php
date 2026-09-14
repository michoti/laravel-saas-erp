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
        DB::statement(<<<'SQL'
            CREATE TYPE sync_batch_status AS ENUM ('queued', 'processing', 'processed', 'failed')
        SQL);

        Schema::create('sync_batches', function (Blueprint $table): void {
            // Client-generated UUIDv7. The uniqueness of this key, submitted
            // by the device, is the entire idempotency mechanism: a batch
            // retried after a dropped connection is a guaranteed no-op.
            $table->uuid('batch_id')->primary();

            $table->uuid('device_id')->index();
            $table->addColumn('sync_batch_status', 'status')->default('queued');

            $table->unsignedSmallInteger('order_count')->default(0);
            $table->json('result_summary')->nullable();
            $table->text('error')->nullable();

            $table->timestampTz('received_at');
            $table->timestampTz('processed_at')->nullable();

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_batches');
        DB::statement('DROP TYPE IF EXISTS sync_batch_status');
    }
};
