<?php

namespace App\Http\Controllers;

use App\Models\Role;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    #[OA\Get(
        path: '/roles',
        summary: 'Listar papéis',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de papéis',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Role'))
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index()
    {
        return response()->json(Role::all());
    }

    #[OA\Get(
        path: '/roles/{role}',
        summary: 'Detalhes de um papel',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Papel',
                content: new OA\JsonContent(ref: '#/components/schemas/Role')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Papel não encontrado'),
        ]
    )]
    public function show(Role $role)
    {
        return response()->json($role);
    }
}
