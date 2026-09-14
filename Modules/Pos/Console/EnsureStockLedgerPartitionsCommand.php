<?php

declare(strict_types=1);

namespace Modules\Pos\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Postgres declarative partitioning requires partitions to exist BEFORE a
 * row lands in their date range — there's no auto-create-on-insert.
 * Scheduled monthly (see routes/console.php), this keeps the next few
 * months of `stock_ledger` partitions provisioned well ahead of when
 * they're actually needed, so this never becomes a race against real
 * traffic.
 */
final class EnsureStockLedgerPartitionsCommand extends Command
{
    protected $signature = 'pos:ensure-stock-ledger-partitions {--months-ahead=3}';

    protected $description = 'Create any missing monthly stock_ledger partitions for the upcoming N months.';

    public function handle(): int
    {
        $monthsAhead = (int) $this->option('months-ahead');
        $start = now()->startOfMonth();

        for ($i = 0; $i <= $monthsAhead; $i++) {
            $from = $start->clone()->addMonths($i);
            $to = $from->clone()->addMonth();
            $suffix = $from->format('Y_m');

            DB::statement(<<<SQL
                CREATE TABLE IF NOT EXISTS stock_ledger_{$suffix}
                PARTITION OF stock_ledger
                FOR VALUES FROM ('{$from->toDateString()}') TO ('{$to->toDateString()}')
            SQL);
        }

        // A row in the DEFAULT partition means something wrote a
        // occurred_at outside every explicit partition — almost always a
        // device with a badly wrong clock rather than a real gap in
        // partition coverage, but worth knowing about either way.
        $strayCount = (int) DB::table('stock_ledger_default')->count();

        if ($strayCount > 0) {
            $this->warn("{$strayCount} row(s) are sitting in the DEFAULT partition — check for devices with an incorrect clock.");
        }

        $this->info("Ensured stock_ledger partitions through {$start->clone()->addMonths($monthsAhead)->format('F Y')}.");

        return self::SUCCESS;
    }
}
