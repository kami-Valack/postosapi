<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSyncOperation extends Model
{
    protected $fillable = [
        'offline_sync_batch_id',
        'client_operation_id',
        'operation_type',
        'payload',
        'client_timestamp',
        'status',
        'conflict_reason',
        'server_result',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'client_timestamp' => 'datetime',
            'server_result' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(OfflineSyncBatch::class, 'offline_sync_batch_id');
    }
}
