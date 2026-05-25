<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPromotion extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'service_id',
        'product_id',
        'title',
        'description',
        'discount_percent',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'float',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeCurrentlyActive($query)
    {
        $now = now();

        return $query->where('status', 'active')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }
}
