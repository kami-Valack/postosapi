<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackCampaignInteractionRequest;
use App\Models\PostCampaign;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CampaignPublicController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService
    ) {}

    #[OA\Get(
        path: '/campaigns/nearby',
        summary: 'Campanhas activas perto do utilizador (RN-G-007)',
        description: 'Rota pública. Valida proximidade geográfica ao posto (raio da campanha).',
        tags: ['Campanhas Públicas'],
        parameters: [
            new OA\Parameter(name: 'latitude', in: 'query', required: true, schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'longitude', in: 'query', required: true, schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Campanhas próximas')]
    )]
    public function nearby(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json([
            'campaigns' => $this->campaignService->nearby(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                $validated['post_id'] ?? null,
                (int) ($validated['limit'] ?? 20)
            ),
        ]);
    }

    #[OA\Post(
        path: '/campaigns/{campaign}/interactions',
        summary: 'Registar visualização/clique/conversão',
        description: 'Exige coordenadas dentro do raio geográfico da campanha.',
        tags: ['Campanhas Públicas'],
        parameters: [
            new OA\Parameter(name: 'campaign', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrackCampaignInteractionRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Interacção registada'),
            new OA\Response(response: 422, description: 'Fora do raio ou campanha inactiva'),
        ]
    )]
    public function track(TrackCampaignInteractionRequest $request, PostCampaign $campaign)
    {
        $result = $this->campaignService->trackInteraction($campaign, $request->validated());

        return response()->json($result);
    }
}
