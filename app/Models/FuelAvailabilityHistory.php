<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelAvailabilityHistory extends Model
{
    protected $fillable = [
        'post_fuel_availability_id',
        'old_availability',
        'new_availability',
        'motivo_fora_stock',
        'user_id',
    ];

    public function availability(): BelongsTo
    {
        return $this->belongsTo(PostFuelAvailability::class, 'post_fuel_availability_id');
    }
}
