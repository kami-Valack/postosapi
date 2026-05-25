<?php

use App\Http\Controllers\AdminIncidentController;
use App\Http\Controllers\AdminPriceDecreeController;
use App\Http\Controllers\GestorIncidentController;
use App\Http\Controllers\GestorCatalogController;
use App\Http\Controllers\GestorPostController;
use App\Http\Controllers\CampaignPublicController;
use App\Http\Controllers\GestorCampaignController;
use App\Http\Controllers\GestorOfflineSyncController;
use App\Http\Controllers\GestorPromotionController;
use App\Http\Controllers\GestorStockAlertController;
use App\Http\Controllers\GestorPriceDecreeController;
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

Route::get('/campaigns/nearby', [CampaignPublicController::class, 'nearby']);
Route::post('/campaigns/{campaign}/interactions', [CampaignPublicController::class, 'track']);

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

Route::get('/admin/price-decrees', [AdminPriceDecreeController::class, 'index']);
Route::post('/admin/price-decrees', [AdminPriceDecreeController::class, 'store'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':admin');

Route::get('/admin/incidents', [AdminIncidentController::class, 'index']);
Route::patch('/admin/incidents/{incident}', [AdminIncidentController::class, 'update'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':admin');

Route::get('/gestor/catalog', [GestorCatalogController::class, 'index'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');

Route::get('/posts/{post}/price-decrees', [GestorPriceDecreeController::class, 'index']);
Route::post('/posts/{post}/price-decrees/{priceDecree}/confirm', [GestorPriceDecreeController::class, 'confirm'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');

Route::get('/posts/{post}/incidents', [GestorIncidentController::class, 'index']);
Route::post('/posts/{post}/incidents', [GestorIncidentController::class, 'store'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::get('/posts/{post}/incidents/{incident}', [GestorIncidentController::class, 'show']);

Route::get('/posts/{post}/operational', [GestorPostController::class, 'show']);
Route::patch('/posts/{post}/operational', [GestorPostController::class, 'update'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');

Route::patch('/posts/{post}/products/{product}/stock', [StockController::class, 'update'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');

Route::get('/posts/{post}/promotions', [GestorPromotionController::class, 'index']);
Route::post('/posts/{post}/promotions', [GestorPromotionController::class, 'store'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::patch('/posts/{post}/promotions/{promotion}', [GestorPromotionController::class, 'update'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::post('/posts/{post}/promotions/{promotion}/cancel', [GestorPromotionController::class, 'cancel'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');

Route::get('/posts/{post}/stock-alerts', [GestorStockAlertController::class, 'index']);
Route::post('/posts/{post}/stock-alerts/analyze', [GestorStockAlertController::class, 'analyze'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::patch('/posts/{post}/stock-alerts/{alert}/acknowledge', [GestorStockAlertController::class, 'acknowledge'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');

Route::get('/posts/{post}/campaigns', [GestorCampaignController::class, 'index']);
Route::post('/posts/{post}/campaigns', [GestorCampaignController::class, 'store'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::patch('/posts/{post}/campaigns/{campaign}', [GestorCampaignController::class, 'update'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::get('/posts/{post}/campaigns/{campaign}/performance', [GestorCampaignController::class, 'performance']);
Route::patch('/posts/{post}/campaigns/{campaign}/feedback', [GestorCampaignController::class, 'feedback'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::post('/posts/{post}/campaigns/{campaign}/pause', [GestorCampaignController::class, 'pause'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::post('/posts/{post}/campaigns/{campaign}/resume', [GestorCampaignController::class, 'resume'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::post('/posts/{post}/campaigns/{campaign}/cancel', [GestorCampaignController::class, 'cancel'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');

Route::post('/posts/{post}/sync', [GestorOfflineSyncController::class, 'store'])
    ->middleware(\App\Http\Middleware\RoleMiddleware::class.':gestor');
Route::get('/posts/{post}/sync/batches', [GestorOfflineSyncController::class, 'index']);
Route::get('/posts/{post}/sync/batches/{batch}', [GestorOfflineSyncController::class, 'show']);

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
    $jsonUrl = config('postos.openapi_json_path', '/api/docs/openapi.json');
    $html = "<!doctype html><html><head><meta charset=\"utf-8\"><title>Postos API Docs</title></head><body>\n".
        "<redoc spec-url=\"{$jsonUrl}\"></redoc>\n".
        "<script src=\"https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js\"></script>\n".
        "</body></html>";

    return response($html, 200)->header('Content-Type', 'text/html');
});
