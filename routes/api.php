<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PokemonMetricsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
    });

    $metrics = Route::get('pokemons/metrics', PokemonMetricsController::class)
        ->name('pokemons.metrics');

    if (config('metrics.require_authentication')) {
        $metrics->middleware('auth:sanctum');
    }
});
