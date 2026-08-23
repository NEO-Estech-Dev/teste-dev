<?php

use App\Http\Controllers\PokemonController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/sessao')->group(function () {
    Route::post('/login', [UserController::class, 'signIn'])->name('sessao.sign-in');
    Route::post('/cadastrar', [UserController::class, 'signUp'])->name('sessao.sign-up');
});

Route::prefix('/pokemon')->middleware('auth:sanctum')->group(function () {
    Route::get('/',[PokemonController::class, 'indexMetrics'])->name('pokemon.indexMetrics');
});
