<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGestorOperationalRequest;
use App\Models\Post;
use App\Services\GestorPostOperationalService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GestorPostController extends Controller
{
    public function __construct(
        private readonly GestorPostOperationalService $operationalService
    ) {}

    #[OA\Get(
        path: '/posts/{post}/operational',
        summary: 'Estado operacional do posto (gestor)',
        description: 'Status do posto, serviços (activos/inactivos) e disponibilidade de combustíveis. Gestor só no seu posto.',
        tags: ['Gestor'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estado operacional', content: new OA\JsonContent(ref: '#/components/schemas/GestorOperationalResponse')),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Posto não encontrado'),
        ]
    )]
    public function show(Request $request, Post $post)
    {
        $this->authorize('manageOperational', $post);

        if ($post->fuelAvailabilities()->count() === 0) {
            $this->operationalService->ensureDefaultFuelAvailabilities($post, $request->user()?->id);
        }

        $post->refresh();
        $post->load(['services', 'fuelAvailabilities.fuelType']);

        return response()->json($this->operationalService->show($post));
    }

    #[OA\Patch(
        path: '/posts/{post}/operational',
        summary: 'Actualizar estado operacional (gestor)',
        description: 'RN-G-004.1 combustíveis, RN-G-005 serviços. Alterar status, serviços e disponibilidade gasolina/gasóleo/elétrico.',
        tags: ['Gestor'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateGestorOperationalRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/GestorOperationalResponse')),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 422, description: 'Validação'),
        ]
    )]
    public function update(UpdateGestorOperationalRequest $request, Post $post)
    {
        $this->authorize('manageOperational', $post);

        $payload = $this->operationalService->update(
            $post,
            $request->user(),
            $request->validated()
        );

        return response()->json($payload);
    }
}
