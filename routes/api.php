<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PokemonMetricsController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
});

Route::get('/pokemons/metrics', PokemonMetricsController::class)
    ->middleware('throttle:api');
