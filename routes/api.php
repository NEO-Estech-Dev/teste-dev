<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\PokemonRankingController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/pokemons/ranking', PokemonRankingController::class)
    ->name('api.v1.pokemons.ranking');
