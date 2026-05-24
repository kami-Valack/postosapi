<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use App\Services\PostoSearchService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PostController extends Controller
{
    #[OA\Get(
        path: '/posts',
        summary: 'Listar posts',
        description: 'Admin vê todos os postos. Gestor com post_id vê apenas o seu posto.',
        tags: ['Posts'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de posts',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Post'))
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && $user->post_id) {
            return response()->json(Post::where('id', $user->post_id)->get());
        }

        return response()->json(Post::all());
    }

    #[OA\Get(
        path: '/posts/{post}',
        summary: 'Detalhes de um posto',
        tags: ['Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Posto',
                content: new OA\JsonContent(ref: '#/components/schemas/Post')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Posto não encontrado'),
        ]
    )]
    public function show(Post $post)
    {
        return response()->json($post);
    }

    #[OA\Post(
        path: '/posts',
        summary: 'Criar posto (admin)',
        tags: ['Posts'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePostRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Posto criado',
                content: new OA\JsonContent(ref: '#/components/schemas/Post')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Apenas admin'),
            new OA\Response(response: 422, description: 'Validação falhou'),
        ]
    )]
    public function store(StorePostRequest $request)
    {
        $this->authorize('create', Post::class);

        $data = $request->validated();
        $serviceNames = $data['services'] ?? null;
        unset($data['services']);

        $post = Post::create($data);

        if (is_array($serviceNames)) {
            $post->syncServiceNames($serviceNames);
        }

        PostoSearchService::flushCache();

        return response()->json($post->load('services'), 201);
    }

    #[OA\Put(
        path: '/posts/{post}',
        summary: 'Atualizar posto (admin)',
        tags: ['Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePostRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Posto atualizado',
                content: new OA\JsonContent(ref: '#/components/schemas/Post')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Apenas admin'),
            new OA\Response(response: 404, description: 'Posto não encontrado'),
            new OA\Response(response: 422, description: 'Validação falhou'),
        ]
    )]
    public function update(StorePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $data = $request->validated();
        $serviceNames = $data['services'] ?? null;
        unset($data['services']);

        $post->update($data);

        if (is_array($serviceNames)) {
            $post->syncServiceNames($serviceNames);
        }

        PostoSearchService::flushCache();

        return response()->json($post->load('services'));
    }

    #[OA\Delete(
        path: '/posts/{post}',
        summary: 'Eliminar posto (admin)',
        tags: ['Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Posto eliminado',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Apenas admin'),
            new OA\Response(response: 404, description: 'Posto não encontrado'),
        ]
    )]
    public function destroy(Request $request, Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        PostoSearchService::flushCache();

        return response()->json(['message' => 'Deleted']);
    }
}
