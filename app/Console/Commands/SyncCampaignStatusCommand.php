<?php

namespace App\Console\Commands;

use App\Services\CampaignService;
use Illuminate\Console\Command;

class SyncCampaignStatusCommand extends Command
{
    protected $signature = 'campaigns:sync-status';

    protected $description = 'RN-G-007: activa ou termina campanhas conforme datas';

    public function handle(CampaignService $service): int
    {
        $count = $service->syncAllStatuses();
        $this->info("Estados actualizados: {$count} campanha(s).");

        return self::SUCCESS;
    }
}
