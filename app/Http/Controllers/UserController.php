<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Rbac;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Patch(
        path: '/users/{user}',
        summary: 'Associar utilizador a posto e/ou papel (admin)',
        description: 'O parâmetro {user} aceita o id local ou o auth_user_id (sub do JWT externo).',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'Id local ou auth_user_id (sub do serviço de auth)',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'post_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'role_id', type: 'integer', nullable: true, example: 3),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Utilizador atualizado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Apenas admin'),
            new OA\Response(response: 404, description: 'Utilizador não encontrado'),
            new OA\Response(response: 422, description: 'Validação falhou'),
        ]
    )]
    public function update(Request $request, int $user)
    {
        if (! Rbac::isAdminRequest($request)) {
            return response()->json(['success' => false, 'message' => 'Forbidden', 'code' => 403], 403);
        }

        $userModel = User::query()
            ->where('id', $user)
            ->orWhere('auth_user_id', $user)
            ->firstOrFail();

        $validated = $request->validate([
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        if (array_key_exists('post_id', $validated)) {
            $userModel->post_id = $validated['post_id'];
        }
        if (array_key_exists('role_id', $validated)) {
            $userModel->role_id = $validated['role_id'];
        }

        $userModel->save();

        return response()->json($userModel->load(['role', 'post']));
    }
}
