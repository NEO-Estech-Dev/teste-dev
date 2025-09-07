<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PokemonController;
use App\Http\Controllers\Api\TypeController;
use App\Http\Controllers\Api\AbilityController;
use App\Http\Controllers\Api\MetricsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Pokemon routes
Route::prefix('pokemon')->group(function () {
    Route::get('/', [PokemonController::class, 'index']);
    Route::get('/{id}', [PokemonController::class, 'show']);
    Route::get('/pokemon-id/{pokemonId}', [PokemonController::class, 'showByPokemonId']);
});

// Type routes
Route::prefix('types')->group(function () {
    Route::get('/', [TypeController::class, 'index']);
    Route::get('/{id}', [TypeController::class, 'show']);
    Route::get('/{id}/pokemon', [TypeController::class, 'pokemon']);
});

// Ability routes
Route::prefix('abilities')->group(function () {
    Route::get('/', [AbilityController::class, 'index']);
    Route::get('/{id}', [AbilityController::class, 'show']);
    Route::get('/{id}/pokemon', [AbilityController::class, 'pokemon']);
});

// Metrics routes
Route::prefix('metrics')->group(function () {
    Route::get('/', [MetricsController::class, 'index']);
    Route::get('/available', [MetricsController::class, 'availableMetrics']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
