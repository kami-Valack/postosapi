<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignFeedbackRequest;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Post;
use App\Models\PostCampaign;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GestorCampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService
    ) {}

    #[OA\Get(
        path: '/posts/{post}/campaigns',
        summary: 'Listar campanhas geolocalizadas (RN-G-007)',
        tags: ['Gestor', 'Campanhas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista de campanhas')]
    )]
    public function index(Request $request, Post $post)
    {
        $this->authorize('manageCampaigns', $post);

        return response()->json([
            'post_id' => $post->id,
            'campaigns' => $this->campaignService->listForPost($post, $request->query('status')),
        ]);
    }

    #[OA\Post(
        path: '/posts/{post}/campaigns',
        summary: 'Criar campanha (não-combustível, geolocalizada)',
        tags: ['Gestor', 'Campanhas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreCampaignRequest')
        ),
        responses: [new OA\Response(response: 201, description: 'Campanha criada')]
    )]
    public function store(StoreCampaignRequest $request, Post $post)
    {
        $this->authorize('manageCampaigns', $post);

        $campaign = $this->campaignService->create($post, $request->user(), $request->validated());

        return response()->json($this->campaignService->format($campaign), 201);
    }

    #[OA\Patch(
        path: '/posts/{post}/campaigns/{campaign}',
        summary: 'Actualizar campanha',
        tags: ['Gestor', 'Campanhas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/UpdateCampaignRequest')),
        responses: [new OA\Response(response: 200, description: 'Actualizada')]
    )]
    public function update(UpdateCampaignRequest $request, Post $post, PostCampaign $campaign)
    {
        $this->authorize('manageCampaigns', $post);
        $this->assertCampaignPost($post, $campaign);

        $campaign = $this->campaignService->update($campaign, $request->user(), $request->validated());

        return response()->json($this->campaignService->format($campaign));
    }

    #[OA\Get(
        path: '/posts/{post}/campaigns/{campaign}/performance',
        summary: 'Métricas de performance (RN-G-007.1)',
        tags: ['Gestor', 'Campanhas'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Métricas')]
    )]
    public function performance(Post $post, PostCampaign $campaign)
    {
        $this->authorize('manageCampaigns', $post);
        $this->assertCampaignPost($post, $campaign);

        return response()->json($this->campaignService->performance($campaign));
    }

    #[OA\Patch(
        path: '/posts/{post}/campaigns/{campaign}/feedback',
        summary: 'Feedback qualitativo da campanha (RN-G-007.1)',
        tags: ['Gestor', 'Campanhas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/CampaignFeedbackRequest')),
        responses: [new OA\Response(response: 200, description: 'Feedback registado')]
    )]
    public function feedback(CampaignFeedbackRequest $request, Post $post, PostCampaign $campaign)
    {
        $this->authorize('manageCampaigns', $post);
        $this->assertCampaignPost($post, $campaign);

        $campaign = $this->campaignService->submitFeedback($campaign, $request->user(), $request->validated());

        return response()->json($this->campaignService->performance($campaign));
    }

    #[OA\Post(
        path: '/posts/{post}/campaigns/{campaign}/pause',
        summary: 'Pausar campanha activa',
        tags: ['Gestor', 'Campanhas'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Pausada')]
    )]
    public function pause(Post $post, PostCampaign $campaign)
    {
        $this->authorize('manageCampaigns', $post);
        $this->assertCampaignPost($post, $campaign);

        return response()->json($this->campaignService->format(
            $this->campaignService->pause($campaign, request()->user())
        ));
    }

    #[OA\Post(
        path: '/posts/{post}/campaigns/{campaign}/resume',
        summary: 'Retomar campanha pausada',
        tags: ['Gestor', 'Campanhas'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Retomada')]
    )]
    public function resume(Post $post, PostCampaign $campaign)
    {
        $this->authorize('manageCampaigns', $post);
        $this->assertCampaignPost($post, $campaign);

        return response()->json($this->campaignService->format(
            $this->campaignService->resume($campaign, request()->user())
        ));
    }

    #[OA\Post(
        path: '/posts/{post}/campaigns/{campaign}/cancel',
        summary: 'Cancelar campanha',
        tags: ['Gestor', 'Campanhas'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Cancelada')]
    )]
    public function cancel(Post $post, PostCampaign $campaign)
    {
        $this->authorize('manageCampaigns', $post);
        $this->assertCampaignPost($post, $campaign);

        return response()->json($this->campaignService->format(
            $this->campaignService->cancel($campaign, request()->user())
        ));
    }

    private function assertCampaignPost(Post $post, PostCampaign $campaign): void
    {
        if ((int) $campaign->post_id !== (int) $post->id) {
            abort(404);
        }
    }
}
