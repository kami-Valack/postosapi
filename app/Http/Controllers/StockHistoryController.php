<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Support\Rbac;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StockHistoryController extends Controller
{
    #[OA\Get(
        path: '/posts/{post}/stock_histories',
        summary: 'Histórico de stock de um posto',
        tags: ['StockHistory'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de registos de histórico',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/StockHistory'))
            ),
            new OA\Response(
                response: 403,
                description: 'Sem permissão',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function indexByPost(Post $post, Request $request)
    {
        $user = $request->user();

        if (! Rbac::isAdminRequest($request, $user) && (int) $user->post_id !== (int) $post->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden', 'code' => 403], 403);
        }

        $stockIds = Stock::where('post_id', $post->id)->pluck('id');
        $histories = StockHistory::whereIn('stock_id', $stockIds)->orderBy('created_at', 'desc')->get();

        return response()->json($histories);
    }

    #[OA\Get(
        path: '/posts/{post}/products/{product}/stock_histories',
        summary: 'Histórico de stock de um produto num posto',
        tags: ['StockHistory'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de registos de histórico',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/StockHistory'))
            ),
            new OA\Response(
                response: 403,
                description: 'Sem permissão',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function indexByPostProduct(Post $post, Product $product, Request $request)
    {
        $user = $request->user();

        if (! Rbac::isAdminRequest($request, $user) && (int) $user->post_id !== (int) $post->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden', 'code' => 403], 403);
        }

        $stock = Stock::where(['post_id' => $post->id, 'product_id' => $product->id])->first();
        if (! $stock) {
            return response()->json([], 200);
        }

        $histories = StockHistory::where('stock_id', $stock->id)->orderBy('created_at', 'desc')->get();

        return response()->json($histories);
    }

    #[OA\Get(
        path: '/products/{product}/stock_histories',
        summary: 'Histórico de stock de um produto',
        description: 'Admin vê todos os postos. Gestor vê apenas o seu posto.',
        tags: ['StockHistory'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de registos de histórico',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/StockHistory'))
            ),
            new OA\Response(
                response: 403,
                description: 'Sem permissão',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function indexByProduct(Product $product, Request $request)
    {
        $user = $request->user();

        $stocks = Stock::where('product_id', $product->id);

        if (! Rbac::isAdminRequest($request, $user)) {
            if (! $user->post_id) {
                return response()->json(['success' => false, 'message' => 'Forbidden', 'code' => 403], 403);
            }
            $stocks->where('post_id', $user->post_id);
        }

        $stockIds = $stocks->pluck('id');
        $histories = StockHistory::whereIn('stock_id', $stockIds)->orderBy('created_at', 'desc')->get();

        return response()->json($histories); 
    }
}
