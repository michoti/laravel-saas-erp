<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The 100 best-selling products by revenue, using the per-line snapshot
 * columns so historical name/SKU edits never distort past sales.
 */
final class TopProductsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function collection(): Collection
    {
        return DB::table('order_items')
            ->selectRaw('product_sku_snapshot, product_name_snapshot, SUM(quantity) as quantity_sold, SUM(line_total) as revenue')
            ->groupBy('product_sku_snapshot', 'product_name_snapshot')
            ->orderByRaw('SUM(line_total) DESC')
            ->limit(100)
            ->get()
            ->map(fn (object $row): array => [
                $row->product_sku_snapshot,
                $row->product_name_snapshot,
                (float) $row->quantity_sold,
                (float) $row->revenue,
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['SKU', 'Product', 'Quantity sold', 'Revenue'];
    }

    public function title(): string
    {
        return 'Top products';
    }
}
