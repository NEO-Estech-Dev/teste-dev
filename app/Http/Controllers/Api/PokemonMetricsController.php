<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PokemonMetricsRequest;
use App\Http\Resources\PokemonMetricsResource;
use App\Queries\PokemonMetricsQuery;
use Illuminate\Http\JsonResponse;

class PokemonMetricsController extends Controller
{
    public function __invoke(PokemonMetricsRequest $request, PokemonMetricsQuery $query): JsonResponse
    {
        $filters = $request->filters();

        $page = $query
            ->paginate($filters)
            ->appends($request->query());

        return (new PokemonMetricsResource($page, $filters))->response();
    }
}
