<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\StockRuptureAlert;
use App\Services\StockRuptureAnalysisService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GestorStockAlertController extends Controller
{
    public function __construct(
        private readonly StockRuptureAnalysisService $analysisService
    ) {}

    #[OA\Get(
        path: '/posts/{post}/stock-alerts',
        summary: 'Alertas preditivos de rutura (RN-G-004)',
        description: 'Lista alertas activos/reconhecidos. Parâmetro `status` opcional. Use POST analyze para forçar análise imediata.',
        tags: ['Gestor', 'Stock'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'acknowledged', 'resolved'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Alertas'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ]
    )]
    public function index(Request $request, Post $post)
    {
        $this->authorize('viewStockAlerts', $post);

        return response()->json([
            'post_id' => $post->id,
            'analysis_window_hours' => (int) config('stock_alerts.analysis_window_hours', 48),
            'rupture_threshold_hours' => (int) config('stock_alerts.rupture_threshold_hours', 24),
            'alerts' => $this->analysisService->listForPost($post, $request->query('status')),
        ]);
    }

    #[OA\Post(
        path: '/posts/{post}/stock-alerts/analyze',
        summary: 'Executar análise preditiva agora',
        tags: ['Gestor', 'Stock'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Análise concluída'),
        ]
    )]
    public function analyze(Post $post)
    {
        $this->authorize('viewStockAlerts', $post);

        $alerts = $this->analysisService->analyzePost($post);

        return response()->json([
            'post_id' => $post->id,
            'generated' => count($alerts),
            'alerts' => collect($alerts)->map(fn ($a) => $this->analysisService->format($a))->values(),
        ]);
    }

    #[OA\Patch(
        path: '/posts/{post}/stock-alerts/{alert}/acknowledge',
        summary: 'Reconhecer alerta de rutura',
        tags: ['Gestor', 'Stock'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'alert', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Alerta reconhecido'),
        ]
    )]
    public function acknowledge(Post $post, StockRuptureAlert $alert)
    {
        $this->authorize('viewStockAlerts', $post);

        if ((int) $alert->post_id !== (int) $post->id) {
            abort(404);
        }

        $alert = $this->analysisService->acknowledge($alert, (int) request()->user()->id);

        return response()->json($this->analysisService->format($alert));
    }
}
