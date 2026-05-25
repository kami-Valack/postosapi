<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfflineSyncBatchRequest;
use App\Models\OfflineSyncBatch;
use App\Models\Post;
use App\Services\OfflineSyncService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GestorOfflineSyncController extends Controller
{
    public function __construct(
        private readonly OfflineSyncService $syncService
    ) {}

    #[OA\Post(
        path: '/posts/{post}/sync',
        summary: 'Sincronizar lote offline (RN-G-008)',
        description: 'Processa operações armazenadas localmente com resolução de conflitos.',
        tags: ['Gestor', 'Offline'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/OfflineSyncBatchRequest')
        ),
        responses: [new OA\Response(response: 200, description: 'Lote processado')]
    )]
    public function store(OfflineSyncBatchRequest $request, Post $post)
    {
        $this->authorize('offlineSync', $post);

        $result = $this->syncService->processBatch($post, $request->user(), $request->validated());

        return response()->json($result);
    }

    #[OA\Get(
        path: '/posts/{post}/sync/batches',
        summary: 'Histórico de sincronizações',
        tags: ['Gestor', 'Offline'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Lista de lotes')]
    )]
    public function index(Request $request, Post $post)
    {
        $this->authorize('offlineSync', $post);

        return response()->json([
            'post_id' => $post->id,
            'allowed_operations' => config('sync.allowed_operations'),
            'conflict_strategies' => config('sync.conflict_strategies'),
            'batches' => $this->syncService->listBatches($post, (int) $request->query('limit', 20)),
        ]);
    }

    #[OA\Get(
        path: '/posts/{post}/sync/batches/{batch}',
        summary: 'Detalhe de um lote de sincronização',
        tags: ['Gestor', 'Offline'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Detalhe do lote')]
    )]
    public function show(Post $post, OfflineSyncBatch $batch)
    {
        $this->authorize('offlineSync', $post);

        if ((int) $batch->post_id !== (int) $post->id) {
            abort(404);
        }

        return response()->json($this->syncService->showBatch($batch));
    }
}
