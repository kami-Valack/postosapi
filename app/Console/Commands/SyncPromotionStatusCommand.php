<?php

namespace App\Console\Commands;

use App\Services\PromotionService;
use Illuminate\Console\Command;

class SyncPromotionStatusCommand extends Command
{
    protected $signature = 'promotions:sync-status';

    protected $description = 'RN-G-002: activa ou termina promoções conforme datas';

    public function handle(PromotionService $service): int
    {
        $updated = $service->syncAllStatuses();
        $this->info("Estados actualizados: {$updated} registo(s).");

        return self::SUCCESS;
    }
}
