<?php

namespace App\Services\Pokemon;

use App\Http\Requests\PokemonMetricsRequest;
use App\Models\Pokemon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PokemonMetricsService
{
    public function paginate(PokemonMetricsRequest $request): LengthAwarePaginator
    {
        $metric = $request->metric();

        return Pokemon::query()
            ->select(['id', 'name', $metric])
            ->orderBy($metric, $request->order())
            ->orderBy('name')
            ->paginate($request->perPage())
            ->withQueryString();
    }
}
