<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Rbac;
use App\Support\RoleIds;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Patch(
        path: '/users/{user}',
        summary: 'Associar gestor a posto (admin)',
        description: 'Apenas utilizadores com role_id=4 (Gestor) podem receber post_id. Admins (1–3) nunca são associados a postos. Quem executa o pedido deve ser admin (1–3).',
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
                    new OA\Property(
                        property: 'post_id',
                        type: 'integer',
                        nullable: true,
                        description: 'Só permitido se o utilizador for Gestor (role_id=4)',
                        example: 1
                    ),
                    new OA\Property(
                        property: 'role_id',
                        type: 'integer',
                        nullable: true,
                        description: 'Use 4 para gestor. Se mudar para 1–3, post_id é removido.',
                        example: 4
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Utilizador atualizado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Apenas admin'),
            new OA\Response(response: 404, description: 'Utilizador não encontrado'),
            new OA\Response(response: 422, description: 'Validação falhou ou utilizador não é gestor'),
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
            'role_id' => ['nullable', 'integer', Rule::in(RoleIds::allIds())],
        ]);

        $effectiveRoleId = array_key_exists('role_id', $validated)
            ? (int) $validated['role_id']
            : (int) $userModel->role_id;

        if (array_key_exists('post_id', $validated) && $validated['post_id'] !== null) {
            if (! RoleIds::isGestorId($effectiveRoleId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Apenas utilizadores com papel Gestor (role_id=4) podem ser associados a um posto.',
                    'code' => 422,
                ], 422);
            }
        }

        if (array_key_exists('role_id', $validated)) {
            $userModel->role_id = $validated['role_id'];

            if (RoleIds::isAdminId((int) $validated['role_id'])) {
                $userModel->post_id = null;
            }
        }

        if (array_key_exists('post_id', $validated)) {
            if ($validated['post_id'] === null || RoleIds::isGestorId($effectiveRoleId)) {
                $userModel->post_id = $validated['post_id'];
            }
        }

        $userModel->save();

        return response()->json($userModel->load(['role', 'post']));
    }
}
