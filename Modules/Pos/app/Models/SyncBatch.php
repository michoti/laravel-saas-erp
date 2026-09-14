<?php

declare(strict_types=1);

namespace Modules\Pos\App\Models;

use Illuminate\Database\Eloquent\Model;

final class SyncBatch extends Model
{
    protected $primaryKey = 'batch_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'result_summary' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
