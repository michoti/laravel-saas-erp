<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Refresh the POS reporting read-models every 5 minutes across all tenants
// (see ARCHITECTURE.md §5). stancl/tenancy's tenants:run command initializes
// each tenant database before invoking the tenant-scoped command.
Schedule::command('tenants:run pos:refresh-dashboard-read-models')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Postgres partitioning requires partitions to exist BEFORE a row needs
// one — monthly is frequent enough to stay well ahead of real traffic
// while keeping this cheap (a handful of `CREATE TABLE IF NOT EXISTS`
// statements per tenant).
Schedule::command('tenants:run pos:ensure-stock-ledger-partitions')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping();

// Feeds the superadmin cross-tenant dashboard (see TenantUsageSnapshot).
Schedule::command('platform:refresh-tenant-usage-snapshots')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// Automatic M-Pesa reconciliation (see ReconcilePendingMpesaPaymentsCommand
// and config/mpesa.php's `reconciliation` settings) — catches any payment
// whose callback never arrived.
Schedule::command('tenants:run pos:reconcile-mpesa-payments')
    ->everyMinute()
    ->withoutOverlapping();

// --- Central subscription billing (these run
// once, against the central database, looping every tenant's
// subscription internally via chunkById()) ---
Schedule::command('billing:generate-due-invoices')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('billing:reconcile-mpesa-payments')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
