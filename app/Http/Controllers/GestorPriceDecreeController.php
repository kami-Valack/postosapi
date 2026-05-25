<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmPriceDecreeRequest;
use App\Models\Post;
use App\Models\PriceDecree;
use App\Services\PriceDecreeService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GestorPriceDecreeController extends Controller
{
    public function __construct(
        private readonly PriceDecreeService $priceDecreeService
    ) {}

    #[OA\Get(
        path: '/posts/{post}/price-decrees',
        summary: 'Preços decretados pendentes/confirmados (gestor)',
        description: 'RN-G-001: lista decretos para o posto do gestor. Gestor não altera valores manualmente.',
        tags: ['Gestor', 'Preços'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de decretos'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ]
    )]
    public function index(Request $request, Post $post)
    {
        $this->authorize('confirm', [$post]);

        return response()->json([
            'post_id' => $post->id,
            'decretos' => $this->priceDecreeService->listForPost($post)->values(),
        ]);
    }

    #[OA\Post(
        path: '/posts/{post}/price-decrees/{priceDecree}/confirm',
        summary: 'Confirmar aplicação de preço decretado (gestor)',
        description: 'RN-G-001: confirma receção; aplica preco ao posto. Se após o prazo, motivo_atraso obrigatório.',
        tags: ['Gestor', 'Preços'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'priceDecree', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/ConfirmPriceDecreeRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Preço confirmado e aplicado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 422, description: 'Já confirmado ou falta motivo_atraso'),
        ]
    )]
    public function confirm(ConfirmPriceDecreeRequest $request, Post $post, PriceDecree $priceDecree)
    {
        $this->authorize('confirm', [$post]);

        $result = $this->priceDecreeService->confirm(
            $post,
            $priceDecree,
            $request->user(),
            $request->input('motivo_atraso')
        );

        return response()->json($result);
    }
}
