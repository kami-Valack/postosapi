<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelType extends Model
{
    protected $fillable = ['slug', 'name', 'sort_order'];

    public function availabilities(): HasMany
    {
        return $this->hasMany(PostFuelAvailability::class);
    }
}
