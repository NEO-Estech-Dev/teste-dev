<?php

namespace App\Queries;

use App\Data\PokemonMetricsFilters;
use App\Models\Pokemon;
use Illuminate\Pagination\LengthAwarePaginator;

class PokemonMetricsQuery
{
    public function paginate(PokemonMetricsFilters $filters): LengthAwarePaginator
    {
        return Pokemon::query()
            ->join('pokemon_stats', 'pokemon_stats.pokemon_id', '=', 'pokemons.id')
            ->join('stats', 'stats.id', '=', 'pokemon_stats.stat_id')
            ->where('stats.name', $filters->metric)
            ->selectRaw("{$filters->selectedColumn()} as {$filters->field}")
            ->orderBy('pokemon_stats.base_stat', $filters->order)
            ->orderBy('pokemon_stats.pokemon_id', $filters->order)
            ->paginate($filters->limit);
    }
}
