<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublishPriceDecreeRequest;
use App\Models\PriceDecree;
use App\Services\PriceDecreeService;
use App\Support\Rbac;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminPriceDecreeController extends Controller
{
    public function __construct(
        private readonly PriceDecreeService $priceDecreeService
    ) {}

    #[OA\Get(
        path: '/admin/price-decrees',
        summary: 'Listar preços decretados (admin)',
        tags: ['Preços'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de decretos'),
            new OA\Response(response: 403, description: 'Apenas admin'),
        ]
    )]
    public function index(Request $request)
    {
        if (! Rbac::isAdminRequest($request)) {
            return response()->json(['message' => 'Forbidden', 'code' => 403], 403);
        }

        $decrees = PriceDecree::query()
            ->with(['fuelType', 'publisher:id,name'])
            ->withCount('confirmations')
            ->orderByDesc('effective_from')
            ->paginate(20);

        return response()->json($decrees);
    }

    #[OA\Post(
        path: '/admin/price-decrees',
        summary: 'Publicar preço decretado ANPG/IRDP (admin)',
        description: 'RN-G-001: define preços; gestores apenas confirmam aplicação no posto.',
        tags: ['Preços'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PublishPriceDecreeRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Decreto publicado'),
            new OA\Response(response: 403, description: 'Apenas admin'),
            new OA\Response(response: 422, description: 'Validação'),
        ]
    )]
    public function store(PublishPriceDecreeRequest $request)
    {
        if (! Rbac::isAdminRequest($request)) {
            return response()->json(['message' => 'Forbidden', 'code' => 403], 403);
        }

        $decree = $this->priceDecreeService->publish(
            $request->validated(),
            $request->user()
        );

        return response()->json($decree, 201);
    }
}
