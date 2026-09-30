<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lives INSIDE each tenant database (see config/tenancy.php ->
 * migration_parameters['--path'], which includes database_path
 * ('migrations/tenant')). Required because session.php's `database` driver
 * resolves its connection lazily from config('database.default') at the
 * moment StartSession runs — which, once tenancy identification runs
 * before StartSession (see AppPanelProvider), is the current tenant's
 * connection. Without this table, every tenant-panel request throws a
 * "relation \"sessions\" does not exist" error instead of a clean login
 * screen.
 *
 * `user_id` is a nullable uuid (not the stock unsignedBigInteger) to match
 * this tenant database's own `users.id` uuid primary key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
