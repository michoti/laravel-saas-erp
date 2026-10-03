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
 * One row per calendar day of paid sales: order count and gross takings,
 * most recent day first.
 */
final class DailySalesSummaryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function collection(): Collection
    {
        return DB::table('orders')
            ->where('status', 'paid')
            ->selectRaw('order_date::date as sales_date, COUNT(*) as order_count, COALESCE(SUM(grand_total), 0) as gross_sales')
            ->groupByRaw('order_date::date')
            ->orderByRaw('order_date::date DESC')
            ->get()
            ->map(fn (object $row): array => [
                $row->sales_date,
                (int) $row->order_count,
                (float) $row->gross_sales,
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Date', 'Orders', 'Gross sales'];
    }

    public function title(): string
    {
        return 'Daily sales';
    }
}
