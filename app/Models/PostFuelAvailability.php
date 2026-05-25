<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostFuelAvailability extends Model
{
    protected $fillable = [
        'post_id',
        'fuel_type_id',
        'availability',
        'motivo_fora_stock',
        'updated_by',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(FuelAvailabilityHistory::class);
    }
}
