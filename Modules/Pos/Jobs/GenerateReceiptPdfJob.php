<?php

declare(strict_types=1);

namespace Modules\Pos\Jobs;

use App\Models\Tenant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Pos\App\Models\Order;

/**
 * PDF generation is CPU-bound and takes 200ms-1s even for a simple receipt
 * — dispatched here so a busy till never blocks on it. Runs on the
 * `exports` queue, same isolation reasoning as GenerateOrdersExportJob.
 */
final class GenerateReceiptPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $orderId) {}

    public function handle(): void
    {
        $order = Order::with(['items', 'payments', 'customer'])->findOrFail($this->orderId);

        if ($order->receipt_number === null) {
            // Assigned once, at first print — see the create_orders/receipt
            // migration for why this is distinct from invoice_number.
            $order->receipt_number = 'RCPT-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
        }

        $order->printed_at = now();
        $order->save();

        $tenant = Tenant::find(tenant('id'));
        $theme = $tenant?->theme ?? [];

        if (! empty($theme['logo_url'])) {
            // dompdf fetches images synchronously at render time, so this
            // must already be a fully-qualified URL (or absolute path) —
            // the value stored by TenantResource's FileUpload component is
            // a relative disk path (e.g. "tenant-logos/xyz.png").
            $theme['logo_url'] = Storage::url($theme['logo_url']);
        }

        $pdf = Pdf::loadView('pos::receipts.receipt', [
            'order' => $order,
            'theme' => $theme,
            'tenantName' => $tenant?->name ?? config('app.name'),
        ])->setPaper([0, 0, 226.77, 600], 'portrait'); // ~80mm thermal receipt width

        $path = "receipts/{$order->id}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        Notification::make()
            ->title("Receipt ready — {$order->invoice_number}")
            ->success()
            ->actions([
                Action::make('download')
                    ->label('Download receipt')
                    ->url(Storage::disk('local')->temporaryUrl($path, now()->addDay()), shouldOpenInNewTab: true),
            ])
            ->sendToDatabase(User::find($order->cashier_user_id) ?? User::first());
    }
}
