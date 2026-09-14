<?php

declare(strict_types=1);

namespace Modules\Pos\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Populates the read-model tables that Filament dashboards and exports read
 * from, keeping heavy aggregate queries off the hot write path (orders,
 * stock_ledger, payments). See ARCHITECTURE.md §5.
 */
final class RefreshDashboardReadModelsCommand extends Command
{
    protected $signature = 'pos:refresh-dashboard-read-models';

    protected $description = 'Refresh POS reporting read-models (stock on hand, daily sales, top products) for the current tenant.';

    public function handle(): int
    {
        $this->info('Refreshing stock_on_hand read-model...');
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS pos_read_model_stock_on_hand AS
            SELECT product_id, SUM(quantity_delta) AS quantity_on_hand, now() AS refreshed_at
            FROM stock_ledger
            GROUP BY product_id
        SQL);

        DB::statement('TRUNCATE pos_read_model_stock_on_hand');
        DB::statement(<<<'SQL'
            INSERT INTO pos_read_model_stock_on_hand (product_id, quantity_on_hand, refreshed_at)
            SELECT product_id, SUM(quantity_delta), now()
            FROM stock_ledger
            GROUP BY product_id
        SQL);

        $this->info('Refreshing daily_sales_summary read-model...');
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS pos_read_model_daily_sales_summary AS
            SELECT order_date::date AS sales_date, COUNT(*) AS order_count, SUM(grand_total) AS gross_sales, now() AS refreshed_at
            FROM orders
            WHERE status = 'paid'
            GROUP BY order_date::date
        SQL);

        DB::statement('TRUNCATE pos_read_model_daily_sales_summary');
        DB::statement(<<<'SQL'
            INSERT INTO pos_read_model_daily_sales_summary (sales_date, order_count, gross_sales, refreshed_at)
            SELECT order_date::date, COUNT(*), SUM(grand_total), now()
            FROM orders
            WHERE status = 'paid'
            GROUP BY order_date::date
        SQL);

        $this->info('Done.');

        return self::SUCCESS;
    }
}
