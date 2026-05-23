<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    protected $fillable = ['stock_id', 'old_quantity', 'new_quantity', 'user_id', 'justificativa_ajuste'];
}
