<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111; margin: 0; padding: 8px; }
        .center { text-align: center; }
        .logo { max-height: 50px; margin-bottom: 4px; }
        .divider { border-top: 1px dashed #999; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .right { text-align: right; }
        .totals td { font-weight: bold; }
        .muted { color: #666; font-size: 8px; }
        .receipt-id { font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; }
    </style>
</head>
<body>

    <div class="center">
        @if (! empty($theme['logo_url']))
            <img src="{{ $theme['logo_url'] }}" class="logo" alt="{{ $tenantName }}">
        @endif
        <div style="font-size:13px; font-weight:bold;">{{ $tenantName }}</div>
        <div class="muted">Receipt #{{ $order->receipt_number }}</div>
        <div class="muted">{{ $order->printed_at->format('Y-m-d H:i:s') }}</div>
    </div>

    <div class="divider"></div>

    <table>
        <tr><td>Invoice</td><td class="right">{{ $order->invoice_number }}</td></tr>
        @if ($order->customer)
            <tr><td>Customer</td><td class="right">{{ $order->customer->name }}</td></tr>
        @endif
        <tr><td>Cashier ref</td><td class="right">{{ substr($order->cashier_user_id ?? '-', 0, 8) }}</td></tr>
    </table>

    <div class="divider"></div>

    <table>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->product_name_snapshot }}<br>
                    <span class="muted">{{ $item->quantity }} × KES {{ number_format($item->unit_price, 2) }}</span>
                </td>
                <td class="right">KES {{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">KES {{ number_format($order->subtotal, 2) }}</td></tr>
        @if ($order->discount_total > 0)
            <tr><td>Discount</td><td class="right">-KES {{ number_format($order->discount_total, 2) }}</td></tr>
        @endif
        <tr><td>Tax</td><td class="right">KES {{ number_format($order->tax_total, 2) }}</td></tr>
        <tr><td>TOTAL</td><td class="right">KES {{ number_format($order->grand_total, 2) }}</td></tr>
    </table>

    <div class="divider"></div>

    <table>
        @foreach ($order->payments as $payment)
            <tr>
                <td>{{ ucfirst($payment->method) }}@if($payment->mpesa_receipt_number) ({{ $payment->mpesa_receipt_number }})@endif</td>
                <td class="right">KES {{ number_format($payment->amount, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <div class="center muted">
        <div class="receipt-id">{{ $order->id }}</div>
        <div>Thank you for shopping with us!</div>
        <div>Powered by {{ config('app.name') }}</div>
    </div>

</body>
</html>
