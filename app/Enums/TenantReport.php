<?php

declare(strict_types=1);

namespace App\Enums;

use App\Exports\DailySalesSummaryExport;
use App\Exports\OrdersExport;
use App\Exports\StockOnHandExport;
use App\Exports\TopProductsExport;

/**
 * The reports a tenant can generate from the POS back office. Each case maps
 * to the maatwebsite export that builds its spreadsheet; the generic
 * GenerateTenantReportJob turns any case into a queued download.
 */
enum TenantReport: string
{
    case Orders = 'orders';
    case StockOnHand = 'stock_on_hand';
    case DailySales = 'daily_sales';
    case TopProducts = 'top_products';

    public function export(): object
    {
        return match ($this) {
            self::Orders => new OrdersExport,
            self::StockOnHand => new StockOnHandExport,
            self::DailySales => new DailySalesSummaryExport,
            self::TopProducts => new TopProductsExport,
        };
    }

    /**
     * Lower-case label used in the "Your … report is ready" notification.
     */
    public function label(): string
    {
        return match ($this) {
            self::Orders => 'orders',
            self::StockOnHand => 'stock on hand',
            self::DailySales => 'daily sales',
            self::TopProducts => 'top products',
        };
    }

    public function filenamePrefix(): string
    {
        return $this->value;
    }
}
