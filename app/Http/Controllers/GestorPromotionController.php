<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromotionRequest;
use App\Http\Requests\UpdatePromotionRequest;
use App\Models\Post;
use App\Models\PostPromotion;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GestorPromotionController extends Controller
{
    public function __construct(
        private readonly PromotionService $promotionService
    ) {}

    #[OA\Get(
        path: '/posts/{post}/promotions',
        summary: 'Listar promoções locais do posto (RN-G-002)',
        tags: ['Gestor', 'Promoções'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['scheduled', 'active', 'ended', 'cancelled'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de promoções'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ]
    )]
    public function index(Request $request, Post $post)
    {
        $this->authorize('managePromotions', $post);

        return response()->json([
            'post_id' => $post->id,
            'max_discount_percent' => (float) config('promotions.max_discount_percent', 25),
            'promotions' => $this->promotionService->listForPost($post, $request->query('status')),
        ]);
    }

    #[OA\Post(
        path: '/posts/{post}/promotions',
        summary: 'Criar promoção local (não-combustível)',
        tags: ['Gestor', 'Promoções'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePromotionRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Promoção criada'),
            new OA\Response(response: 422, description: 'Validação'),
        ]
    )]
    public function store(StorePromotionRequest $request, Post $post)
    {
        $this->authorize('managePromotions', $post);

        $promotion = $this->promotionService->create($post, $request->user(), $request->validated());

        return response()->json($this->promotionService->format($promotion), 201);
    }

    #[OA\Patch(
        path: '/posts/{post}/promotions/{promotion}',
        summary: 'Actualizar promoção (antes de terminar)',
        tags: ['Gestor', 'Promoções'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'promotion', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/UpdatePromotionRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Promoção actualizada'),
            new OA\Response(response: 404, description: 'Não encontrada'),
        ]
    )]
    public function update(UpdatePromotionRequest $request, Post $post, PostPromotion $promotion)
    {
        $this->authorize('managePromotions', $post);

        if ((int) $promotion->post_id !== (int) $post->id) {
            abort(404);
        }

        $promotion = $this->promotionService->update($promotion, $request->user(), $request->validated());

        return response()->json($this->promotionService->format($promotion));
    }

    #[OA\Post(
        path: '/posts/{post}/promotions/{promotion}/cancel',
        summary: 'Cancelar promoção',
        tags: ['Gestor', 'Promoções'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'promotion', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Promoção cancelada'),
        ]
    )]
    public function cancel(Request $request, Post $post, PostPromotion $promotion)
    {
        $this->authorize('managePromotions', $post);

        if ((int) $promotion->post_id !== (int) $post->id) {
            abort(404);
        }

        $promotion = $this->promotionService->cancel($promotion, $request->user());

        return response()->json($this->promotionService->format($promotion));
    }
}
