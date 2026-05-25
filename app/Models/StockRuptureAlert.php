<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRuptureAlert extends Model
{
    protected $fillable = [
        'post_id',
        'product_id',
        'stock_id',
        'current_quantity',
        'avg_hourly_consumption',
        'hours_until_rupture',
        'predicted_rupture_at',
        'severity',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'float',
            'avg_hourly_consumption' => 'float',
            'hours_until_rupture' => 'float',
            'predicted_rupture_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['active', 'acknowledged']);
    }
}
