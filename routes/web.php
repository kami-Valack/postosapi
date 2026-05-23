<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Rota de teste para validar o middleware JWT
Route::get('/jwt-test', function (Request $request) {
    $payload = $request->attributes->get('jwt_payload');

    if (! $payload) {
        return response()->json(['message' => 'No JWT payload present'], 401);
    }

    return response()->json([
        'message' => 'JWT válido',
        'payload' => $payload,
    ]);
});
