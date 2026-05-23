<?php

namespace App\Observers;

use App\Models\Stock;
use App\Models\StockHistory;

class StockObserver
{
    public function updated(Stock $stock): void
    {
        // Create history if not already created elsewhere
        // Note: controller already creates StockHistory; this is a safety net for other updates
        $original = $stock->getOriginal('quantity');
        $current = $stock->quantity;

        if ($original !== $current) {
            StockHistory::create([
                'stock_id' => $stock->id,
                'old_quantity' => $original,
                'new_quantity' => $current,
                'user_id' => null,
                'justificativa_ajuste' => null,
            ]);
        }
    }
}
