<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPriceConfirmation extends Model
{
    protected $fillable = [
        'post_id',
        'price_decree_id',
        'user_id',
        'confirmed_at',
        'motivo_atraso',
        'was_late',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'was_late' => 'boolean',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function priceDecree(): BelongsTo
    {
        return $this->belongsTo(PriceDecree::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
