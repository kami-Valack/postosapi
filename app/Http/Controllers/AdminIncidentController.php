<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateIncidentStatusRequest;
use App\Models\Incident;
use App\Services\IncidentService;
use App\Support\Rbac;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminIncidentController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidentService
    ) {}

    #[OA\Get(
        path: '/admin/incidents',
        summary: 'Listar todos os incidentes (admin)',
        description: 'RN-G-006: visão global para resolução. Filtros: status, category, post_id.',
        tags: ['Incidentes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'string', enum: ['urgente', 'normal'])),
            new OA\Parameter(name: 'post_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada'),
            new OA\Response(response: 403, description: 'Apenas admin'),
        ]
    )]
    public function index(Request $request)
    {
        if (! Rbac::isAdminRequest($request)) {
            return response()->json(['message' => 'Forbidden', 'code' => 403], 403);
        }

        $query = Incident::query()
            ->with(['photos', 'post:id,name', 'reporter:id,name', 'service:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }
        if ($request->filled('post_id')) {
            $query->where('post_id', $request->query('post_id'));
        }

        $paginated = $query->paginate(20);

        return response()->json([
            'data' => collect($paginated->items())->map(fn ($i) => $this->incidentService->format($i)),
            'page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    #[OA\Patch(
        path: '/admin/incidents/{incident}',
        summary: 'Actualizar estado do incidente (admin)',
        tags: ['Incidentes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'incident', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateIncidentStatusRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/IncidentResponse')),
            new OA\Response(response: 403, description: 'Apenas admin'),
        ]
    )]
    public function update(UpdateIncidentStatusRequest $request, Incident $incident)
    {
        if (! Rbac::isAdminRequest($request)) {
            return response()->json(['message' => 'Forbidden', 'code' => 403], 403);
        }

        $incident = $this->incidentService->updateStatus(
            $incident,
            $request->user(),
            $request->validated()
        );

        return response()->json($this->incidentService->format($incident));
    }
}
