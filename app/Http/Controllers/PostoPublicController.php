<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostoPublicResource;
use App\Models\Post;
use App\Services\PostoSearchService;
use App\Services\CampaignService;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PostoPublicController extends Controller
{
    public function __construct(
        private readonly PostoSearchService $postoSearch,
        private readonly PromotionService $promotionService,
        private readonly CampaignService $campaignService
    ) {}

    #[OA\Get(
        path: '/postos/search',
        summary: 'Pesquisar postos (público, rápido)',
        description: <<<'DESC'
**Rota pública** — não requer `Authorization`.

Pesquisa em `nome`, `endereco` e `combustivel` (apenas postos activos).

- `q`: obrigatório, mínimo 2 caracteres
- `limit`: opcional (default 20, máximo 50)
- Filtros opcionais: `tipo`, `combustivel`, `status`
- Resposta em cache ~60 segundos

Exemplo: `GET /api/postos/search?q=shell&status=aberto&limit=10`
DESC,
        tags: ['Postos Públicos'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: true,
                description: 'Termo de pesquisa (nome, morada ou combustível)',
                schema: new OA\Schema(type: 'string', minLength: 2, example: 'shell')
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Máximo de resultados',
                schema: new OA\Schema(type: 'integer', default: 20, maximum: 50, example: 20)
            ),
            new OA\Parameter(
                name: 'tipo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'combustivel')
            ),
            new OA\Parameter(
                name: 'combustivel',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Gasolina')
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'aberto')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de postos que correspondem à pesquisa',
                content: new OA\JsonContent(ref: '#/components/schemas/PostoSearchResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validação falhou (ex.: q em falta ou &lt; 2 caracteres)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The q field is required.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:'.config('postos.search.min_query_length', 2), 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.config('postos.search.max_limit', 50)],
            'tipo' => ['nullable', 'string', 'max:50'],
            'combustivel' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json($this->postoSearch->search($validated));
    }

    #[OA\Get(
        path: '/postos',
        summary: 'Listar postos (público)',
        description: <<<'DESC'
**Rota pública** — não requer `Authorization`.

- Sem parâmetros: todos os postos activos (array)
- `?id=1`: um posto (`PostoPublic`)
- `?page=2`: paginação, 10 por página (`PostoPublicPaginatedResponse`)

Para pesquisa textual use `GET /postos/search?q=...`.
DESC,
        tags: ['Postos Públicos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'query',
                required: false,
                description: 'ID do posto',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Número da página (10 postos por página)',
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Posto único, lista ou página paginada',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(ref: '#/components/schemas/PostoPublic'),
                        new OA\Schema(
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/PostoPublic')
                        ),
                        new OA\Schema(ref: '#/components/schemas/PostoPublicPaginatedResponse'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Posto não encontrado'),
        ]
    )]
    public function index(Request $request)
    {
        if ($request->filled('id')) {
            $post = Post::query()
                ->publicActive()
                ->with(['services', 'fuelAvailabilities.fuelType'])
                ->find($request->query('id'));

            if (! $post) {
                return response()->json(['message' => 'Posto não encontrado'], 404);
            }

            return response()->json(array_merge(
                (new PostoPublicResource($post))->resolve(),
                [
                    'promocoes' => $this->promotionService->activeForPublic($post),
                    'campanhas' => $this->campaignService->activeForPost($post),
                ]
            ));
        }

        $query = Post::query()
            ->publicActive()
            ->with(['services', 'fuelAvailabilities.fuelType'])
            ->orderBy('name');

        if ($request->has('page')) {
            $page = max(1, (int) $request->query('page', 1));
            $paginated = $query->paginate(10, ['*'], 'page', $page);

            return response()->json([
                'postos' => PostoPublicResource::collection($paginated)->resolve(),
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]);
        }

        $postos = $query->get();

        return response()->json(PostoPublicResource::collection($postos)->resolve());
    }
}
