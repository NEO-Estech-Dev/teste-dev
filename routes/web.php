<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'message' => 'API REST de métricas de Pokémon',
        'documentation' => url('/docs/api'),
        'endpoints' => [
            'POST /api/register',
            'POST /api/login',
            'POST /api/logout',
            'GET /api/user',
            'GET /api/pokemons/metrics',
            'GET /docs/api',
            'GET /up',
        ],
    ]);
});
