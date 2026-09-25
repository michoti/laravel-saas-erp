<?php

declare(strict_types=1);

namespace Modules\Pos\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Pos\Http\Requests\StoreSyncBatchRequest;
use Modules\Pos\Jobs\ProcessSyncBatchJob;
use Modules\Pos\App\Models\SyncBatch;

final class SyncBatchController extends Controller
{
    /**
     * POST /api/pos/sync/batch
     *
     * Ingests one atomic batch of offline-generated orders/items/payments
     * from a POS device. Idempotent on `batch_id`: a retried submission
     * (e.g. after a dropped connection before the 202 was received) is a
     * safe no-op that returns the previously recorded outcome.
     */
    public function store(StoreSyncBatchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $existing = SyncBatch::find($data['batch_id']);

        if ($existing !== null) {
            return response()->json([
                'batch_id' => $existing->batch_id,
                'status' => $existing->status,
                'result' => $existing->result_summary,
                'idempotent_replay' => true,
            ], 202);
        }

        $batch = SyncBatch::create([
            'batch_id' => $data['batch_id'],
            'device_id' => $data['device_id'],
            'status' => 'queued',
            'order_count' => count($data['orders']),
            'received_at' => now(),
        ]);

        // Dispatched onto the tenant's dedicated `pos-sync` queue, which
        // Horizon runs with a single concurrent worker per tenant to
        // guarantee strictly sequential ledger writes (see ARCHITECTURE.md §2).
        ProcessSyncBatchJob::dispatch($batch->batch_id, $data['orders'])
            ->onQueue('pos-sync');

        return response()->json([
            'batch_id' => $batch->batch_id,
            'status' => 'queued',
            'idempotent_replay' => false,
        ], 202);
    }

    /**
     * GET /api/pos/sync/batch/{batch_id}
     *
     * Devices poll this after reconnecting to discover the outcome of a
     * batch that may have been processed while they were offline, and to
     * retrieve server-assigned invoice numbers.
     */
    public function show(string $batchId): JsonResponse
    {
        $batch = SyncBatch::findOrFail($batchId);

        return response()->json([
            'batch_id' => $batch->batch_id,
            'status' => $batch->status,
            'result' => $batch->result_summary,
            'error' => $batch->error,
            'processed_at' => $batch->processed_at,
        ]);
    }
}
