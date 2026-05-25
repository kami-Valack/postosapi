<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\StockRuptureAnalysisService;
use Illuminate\Console\Command;

class AnalyzeStockRuptureCommand extends Command
{
    protected $signature = 'stock:analyze-rupture-risk {--post= : ID do posto (opcional)}';

    protected $description = 'RN-G-004: analisa histórico de stock e gera alertas preditivos de rutura';

    public function handle(StockRuptureAnalysisService $service): int
    {
        $postId = $this->option('post');

        if ($postId) {
            $post = Post::query()->find($postId);
            if (! $post) {
                $this->error("Posto {$postId} não encontrado.");

                return self::FAILURE;
            }
            $count = count($service->analyzePost($post));
            $this->info("Posto {$postId}: {$count} alerta(s) actualizado(s).");

            return self::SUCCESS;
        }

        $count = $service->analyzeAll();
        $this->info("Análise global: {$count} alerta(s) actualizado(s).");

        return self::SUCCESS;
    }
}
