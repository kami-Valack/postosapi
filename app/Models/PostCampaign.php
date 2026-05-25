<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCampaign extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'service_id',
        'product_id',
        'title',
        'description',
        'discount_percent',
        'budget_amount',
        'spent_amount',
        'radius_meters',
        'starts_at',
        'ends_at',
        'status',
        'views_count',
        'clicks_count',
        'conversions_count',
        'feedback_qualitativo',
        'feedback_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'float',
            'budget_amount' => 'float',
            'spent_amount' => 'float',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'feedback_submitted_at' => 'datetime',
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

    public function interactions(): HasMany
    {
        return $this->hasMany(CampaignInteraction::class);
    }

    public function scopeCurrentlyActive($query)
    {
        $now = now();

        return $query->where('status', 'active')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }
}
