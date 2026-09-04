<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PokemonRankingRequest;
use App\Http\Resources\Api\V1\RankedPokemonResource;
use App\Queries\RankPokemonsByMetricQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PokemonRankingController extends Controller
{
    /**
     * Consultar ranking de Pokémon.
     *
     * Retorna dados locais, paginados e ordenados pela métrica solicitada.
     *
     * @operationId getPokemonRanking
     */
    public function __invoke(
        PokemonRankingRequest $request,
        RankPokemonsByMetricQuery $rankPokemonsByMetricQuery,
    ): AnonymousResourceCollection {
        $pokemons = $rankPokemonsByMetricQuery->handle(
            metric: $request->metric(),
            field: $request->field(),
            order: $request->order(),
            page: $request->page(),
            perPage: $request->perPage(),
        );

        return RankedPokemonResource::collection($pokemons)
            ->withQuery($request->paginationParameters())
            ->additional([
                'meta' => [
                    'metric' => $request->metric(),
                    'field' => $request->field(),
                    'order' => $request->order(),
                ],
            ]);
    }
}
