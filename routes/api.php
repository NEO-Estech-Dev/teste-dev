<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PokemonMetricController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('/auth/token', [AuthController::class, 'logout']);
        Route::get('/pokemon/metrics', PokemonMetricController::class);
    });
});
