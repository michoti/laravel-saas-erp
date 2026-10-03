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
 * Current stock on hand per product, summed live from the append-only
 * stock_ledger so it never depends on the dashboard read-model refresh
 * having run. Flags any product sitting below its reorder level.
 */
final class StockOnHandExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function collection(): Collection
    {
        return DB::table('products as p')
            ->leftJoin('stock_ledger as sl', 'sl.product_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->selectRaw('p.sku, p.name, p.reorder_level, COALESCE(SUM(sl.quantity_delta), 0) as quantity_on_hand')
            ->groupBy('p.id', 'p.sku', 'p.name', 'p.reorder_level')
            ->orderBy('p.name')
            ->get()
            ->map(fn (object $row): array => [
                $row->sku,
                $row->name,
                (float) $row->quantity_on_hand,
                (int) $row->reorder_level,
                (float) $row->quantity_on_hand < (int) $row->reorder_level ? 'Yes' : 'No',
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['SKU', 'Name', 'Quantity on hand', 'Reorder level', 'Below reorder level'];
    }

    public function title(): string
    {
        return 'Stock on hand';
    }
}
