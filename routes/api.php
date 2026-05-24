<?php

use App\Http\Controllers\PostoPublicController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockHistoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rotas públicas B2C (sem JWT)
Route::get('/postos/search', [PostoPublicController::class, 'search']);
Route::get('/postos', [PostoPublicController::class, 'index']);

Route::get('/me', MeController::class);

Route::get('/roles', [RoleController::class, 'index']);
Route::get('/roles/{role}', [RoleController::class, 'show']);

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::post('/posts', [PostController::class, 'store'])->middleware(\App\Http\Middleware\RoleMiddleware::class.':admin');
Route::put('/posts/{post}', [PostController::class, 'update'])->middleware(\App\Http\Middleware\RoleMiddleware::class.':admin');
Route::delete('/posts/{post}', [PostController::class, 'destroy'])->middleware(\App\Http\Middleware\RoleMiddleware::class.':admin');

Route::patch('/users/{user}', [UserController::class, 'update'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':admin');

Route::patch('/posts/{post}/products/{product}/stock', [StockController::class, 'update'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');

Route::get('/posts/{post}/stock_histories', [StockHistoryController::class, 'indexByPost']);
Route::get('/posts/{post}/products/{product}/stock_histories', [StockHistoryController::class, 'indexByPostProduct']);
Route::get('/products/{product}/stock_histories', [StockHistoryController::class, 'indexByProduct']);

Route::get('/docs/openapi.json', function () {
    $path = storage_path('api-docs/api-docs.json');
    if (! file_exists($path)) {
        $path = public_path('openapi.json');
    }
    if (! file_exists($path)) {
        return response()->json(['message' => 'OpenAPI spec not generated. Run: php artisan l5-swagger:generate'], 404);
    }

    return response()->file($path, ['Content-Type' => 'application/json']);
});

Route::get('/docs', function () {
    $jsonUrl = url('/api/docs/openapi.json');
    $html = "<!doctype html><html><head><meta charset=\"utf-8\"><title>Postos API Docs</title></head><body>\n".
        "<redoc spec-url=\"{$jsonUrl}\"></redoc>\n".
        "<script src=\"https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js\"></script>\n".
        "</body></html>";

    return response($html, 200)->header('Content-Type', 'text/html');
});
