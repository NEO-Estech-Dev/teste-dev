<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PokemonController;
use App\Http\Controllers\Api\TypeController;
use App\Http\Controllers\Api\AbilityController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\AuthController;

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

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::prefix('metrics')->group(function () {
    Route::get('/', [MetricsController::class, 'index']);
    Route::get('/available', [MetricsController::class, 'availableMetrics']);
});

// Protected routes (authentication required for all API endpoints)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth protected routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/user', [AuthController::class, 'user']);
    });

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
});
