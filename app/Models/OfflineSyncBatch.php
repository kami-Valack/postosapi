<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineSyncBatch extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'device_id',
        'status',
        'operations_total',
        'operations_applied',
        'operations_conflicted',
        'operations_rejected',
        'client_batch_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'client_batch_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(OfflineSyncOperation::class);
    }
}
