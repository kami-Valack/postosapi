<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\RoleIds;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    #[OA\Get(
        path: '/roles',
        summary: 'Listar papéis (catálogo local)',
        description: 'IDs fixos desta API: 1=Super Admin Premium, 2=Super Admin, 3=Admin, 4=Gestor. Ver docs/ROLES.md.',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Catálogo de papéis com id, name, type e description',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RoleCatalogEntry')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index()
    {
        return response()->json([
            'roles' => array_values(RoleIds::catalog()),
            'reference' => 'docs/ROLES.md',
        ]);
    }

    #[OA\Get(
        path: '/roles/{role}',
        summary: 'Detalhes de um papel',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'role',
                in: 'path',
                required: true,
                description: '1=Super Admin Premium, 2=Super Admin, 3=Admin, 4=Gestor',
                schema: new OA\Schema(type: 'integer', enum: [1, 2, 3, 4])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Papel',
                content: new OA\JsonContent(ref: '#/components/schemas/RoleCatalogEntry')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Papel não encontrado'),
        ]
    )]
    public function show(Role $role)
    {
        $id = (int) $role->id;
        $entry = RoleIds::catalog()[$id] ?? [
            'id' => $id,
            'name' => $role->name,
            'type' => 'unknown',
            'description' => '',
        ];

        return response()->json($entry);
    }
}
