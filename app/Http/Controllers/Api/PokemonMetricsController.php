<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PokemonMetricsRequest;
use App\Http\Resources\PokemonMetricResource;
use App\Services\Pokemon\PokemonMetricsService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PokemonMetricsController extends Controller
{
    /**
     * Ranking de Pokémon por métrica.
     *
     * Todos os query params são opcionais.
     * Padrões: metric=hp, order=desc, fields=name,value, per_page=20.
     */
    public function __invoke(
        PokemonMetricsRequest $request,
        PokemonMetricsService $service
    ): AnonymousResourceCollection {
        return PokemonMetricResource::collection($service->paginate($request));
    }
}
