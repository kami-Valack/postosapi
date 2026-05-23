<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MeController extends Controller
{
    #[OA\Get(
        path: '/me',
        summary: 'Utilizador sincronizado + claims JWT',
        description: 'O utilizador local é criado/atualizado automaticamente a partir do JWT do serviço de auth externo.',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Perfil local e payload JWT'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function __invoke(Request $request)
    {
        $payload = $request->attributes->get('jwt_payload');
        $user = $request->user();

        return response()->json([
            'user' => $user?->load(['role', 'post']),
            'jwt' => is_object($payload) ? json_decode(json_encode($payload), true) : $payload,
        ]);
    }
}
