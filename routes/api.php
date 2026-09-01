<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PokemonMetricController;
use App\Http\Controllers\Api\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas públicas de autenticação
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas (Requer autenticação via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/metrics/pokemons', [PokemonMetricController::class, 'index']);
});