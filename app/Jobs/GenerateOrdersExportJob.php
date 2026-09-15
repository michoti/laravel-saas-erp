<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exports\OrdersExport;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Runs entirely off the request/response cycle on the `exports` queue.
 * stancl/tenancy's QueueTenancyBootstrapper automatically re-initializes
 * the correct tenant database connection when this job is picked up, so no
 * manual tenant lookup/switch is needed here despite $tenantId being passed
 * through only for logging/traceability.
 */
final class GenerateOrdersExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // large exports can legitimately take minutes; keep off the web worker entirely

    public function __construct(
        public readonly string $userId,
        public readonly string $tenantId,
    ) {}

    public function handle(): void
    {
        $filename = 'exports/orders-'.now()->format('Y-m-d-His').'.xlsx';

        Excel::store(new OrdersExport, $filename, 'local');

        $downloadUrl = Storage::disk('local')->temporaryUrl($filename, now()->addDay());

        $user = User::find($this->userId);

        if ($user === null) {
            Storage::disk('local')->delete($filename);

            return;
        }

        Notification::make()
            ->title('Your orders export is ready')
            ->body('Click below to download. This link expires in 24 hours.')
            ->success()
            ->actions([
                Action::make('download')->label('Download')->url($downloadUrl, shouldOpenInNewTab: true),
            ])
            ->sendToDatabase($user);
    }
}
