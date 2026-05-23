<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['name', 'address', 'latitude', 'longitude', 'admin_id', 'is_active'];
}
