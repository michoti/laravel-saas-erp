<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\Pos\App\Models\Product;
use Tests\TestCase;

final class ProductLowStockQueryTest extends TestCase
{
    public function test_low_stock_queries_use_a_correlated_sum_without_alias_having(): void
    {
        $sql = Product::query()
            ->withSum('stockLedgerEntries as stock_on_hand', 'quantity_delta')
            ->lowStock()
            ->toSql();

        $this->assertStringNotContainsString('having', strtolower($sql));
        $this->assertStringContainsString('stock_ledger', strtolower($sql));
        $this->assertStringContainsString('sum', strtolower($sql));
    }
}
