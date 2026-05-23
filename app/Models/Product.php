<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'unit', 'is_combustible'];
    protected $casts = ['is_combustible' => 'boolean'];
}
