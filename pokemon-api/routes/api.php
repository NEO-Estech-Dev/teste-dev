<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\PokemonMetricController;
use App\Http\Controllers\PokemonSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'store']);
Route::get('/metrics', [PokemonMetricController::class, 'index']);
Route::post('/pokemon/sync', [PokemonSyncController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
