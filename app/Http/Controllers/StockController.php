<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockRequest;
use App\Models\Post;
use App\Models\Product;
use App\Models\Stock;
use OpenApi\Attributes as OA;

class StockController extends Controller
{
    #[OA\Patch(
        path: '/posts/{post}/products/{product}/stock',
        summary: 'Atualizar stock de um produto no posto (gestor)',
        description: 'O gestor só pode atualizar stock do posto ao qual está vinculado (user.post_id === post.id).',
        tags: ['Stock'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateStockRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Stock atualizado',
                content: new OA\JsonContent(ref: '#/components/schemas/StockUpdateResponse')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(
                response: 403,
                description: 'Sem permissão',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(response: 422, description: 'Validação falhou'),
        ]
    )]
    public function update(Post $post, Product $product, StoreStockRequest $request)
    {
        $this->authorize('manageStock', $post);

        $stock = Stock::firstOrNew([
            'post_id' => $post->id,
            'product_id' => $product->id,
        ]);

        $old = $stock->quantity ?? 0;
        $stock->quantity = $request->input('quantity');
        if ($request->filled('critical_level')) {
            $stock->critical_level = $request->input('critical_level');
        }
        $stock->save();

        \App\Models\StockHistory::create([
            'stock_id' => $stock->id,
            'old_quantity' => $old,
            'new_quantity' => $stock->quantity,
            'user_id' => $request->user()?->id,
            'justificativa_ajuste' => $request->input('justificativa_ajuste'),
        ]);

        return response()->json(['message' => 'Stock updated', 'stock' => $stock]);
    }
}
