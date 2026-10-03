<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Pos\App\Models\Order;

/**
 * Every order with its monetary totals. Chunked over the UUID primary key as
 * a stable tie-breaker, as FromQuery requires for safe pagination.
 */
final class OrdersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function query(): Builder
    {
        return Order::query()
            ->orderByDesc('order_date')
            ->orderBy('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Invoice #', 'Reference', 'Status', 'Order date', 'Subtotal', 'Tax', 'Discount', 'Grand total', 'Currency'];
    }

    /**
     * @param  Order  $order
     * @return array<int, mixed>
     */
    public function map(mixed $order): array
    {
        return [
            $order->invoice_number,
            $order->local_reference,
            $order->status->value,
            $order->order_date?->toDateTimeString(),
            (float) $order->subtotal,
            (float) $order->tax_total,
            (float) $order->discount_total,
            (float) $order->grand_total,
            $order->currency,
        ];
    }

    public function title(): string
    {
        return 'Orders';
    }
}
