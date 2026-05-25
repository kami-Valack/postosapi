<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceDecree extends Model
{
    protected $fillable = [
        'reference',
        'fuel_type_id',
        'preco',
        'preco_premium',
        'effective_from',
        'confirmation_deadline',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'datetime',
            'confirmation_deadline' => 'datetime',
        ];
    }

    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(PostPriceConfirmation::class);
    }

    public function isPastDeadline(): bool
    {
        return $this->confirmation_deadline !== null
            && now()->isAfter($this->confirmation_deadline);
    }
}
