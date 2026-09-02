<?php

use App\Http\Controllers\PokemonMetricController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/metrics', [PokemonMetricController::class, 'index']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
