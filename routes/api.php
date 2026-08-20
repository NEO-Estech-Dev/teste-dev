<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PokemonsController;

Route::get('/pokemons/metrics', [PokemonsController::class, 'metrics']);