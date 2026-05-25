<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentRequest;
use App\Models\Incident;
use App\Models\Post;
use App\Services\IncidentService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GestorIncidentController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidentService
    ) {}

    #[OA\Get(
        path: '/posts/{post}/incidents',
        summary: 'Listar incidentes do posto (gestor)',
        tags: ['Gestor', 'Incidentes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de incidentes'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ]
    )]
    public function index(Request $request, Post $post)
    {
        $this->authorize('reportIncident', $post);

        $incidents = $this->incidentService->listForPost($post, $request->query('status'));

        return response()->json([
            'post_id' => $post->id,
            'incidents' => $incidents->map(fn ($i) => $this->incidentService->format($i))->values(),
        ]);
    }

    #[OA\Post(
        path: '/posts/{post}/incidents',
        summary: 'Reportar incidente (gestor, RN-G-006)',
        description: 'Categorias: urgente, normal. Tipos: bomba, servico, energia, ev_charger, outro. Fotos opcionais (multipart).',
        tags: ['Gestor', 'Incidentes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/StoreIncidentRequest')
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Incidente criado', content: new OA\JsonContent(ref: '#/components/schemas/IncidentResponse')),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 422, description: 'Validação'),
        ]
    )]
    public function store(StoreIncidentRequest $request, Post $post)
    {
        $this->authorize('reportIncident', $post);

        $incident = $this->incidentService->create(
            $post,
            $request->user(),
            $request->validated(),
            $request->file('photos', [])
        );

        return response()->json($this->incidentService->format($incident), 201);
    }

    #[OA\Get(
        path: '/posts/{post}/incidents/{incident}',
        summary: 'Detalhe de um incidente',
        tags: ['Gestor', 'Incidentes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'incident', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Incidente', content: new OA\JsonContent(ref: '#/components/schemas/IncidentResponse')),
            new OA\Response(response: 404, description: 'Não encontrado'),
        ]
    )]
    public function show(Post $post, Incident $incident)
    {
        $this->authorize('reportIncident', $post);

        if ((int) $incident->post_id !== (int) $post->id) {
            abort(404);
        }

        return response()->json($this->incidentService->format($incident));
    }
}
