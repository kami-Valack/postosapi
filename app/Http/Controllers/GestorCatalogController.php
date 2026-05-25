<?php

namespace App\Http\Controllers;

use App\Models\FuelType;
use App\Models\Service;
use OpenApi\Attributes as OA;

class GestorCatalogController extends Controller
{
    #[OA\Get(
        path: '/gestor/catalog',
        summary: 'Catálogo para gestão operacional',
        description: 'Tipos de combustível e serviços aprovados para configurar no posto.',
        tags: ['Gestor'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Catálogo'),
        ]
    )]
    public function index()
    {
        return response()->json([
            'combustiveis' => FuelType::query()->orderBy('sort_order')->get(['id', 'slug', 'name']),
            'disponibilidade_valores' => config('fuel_types.availability'),
            'status_posto' => config('fuel_types.post_status'),
            'servicos' => Service::query()->orderBy('name')->get(['id', 'name']),
            'incidentes' => [
                'categories' => config('incidents.categories'),
                'equipment_types' => config('incidents.equipment_types'),
                'statuses' => config('incidents.statuses'),
                'max_photos' => config('incidents.photos.max_files'),
            ],
            'campanhas' => [
                'max_discount_percent' => config('campaigns.max_discount_percent'),
                'default_radius_meters' => config('campaigns.default_radius_meters'),
                'max_radius_meters' => config('campaigns.max_radius_meters'),
                'interaction_types' => config('campaigns.interaction_types'),
            ],
            'offline_sync' => [
                'allowed_operations' => config('sync.allowed_operations'),
                'max_operations_per_batch' => config('sync.max_operations_per_batch'),
                'conflict_strategies' => config('sync.conflict_strategies'),
            ],
        ]);
    }
}
