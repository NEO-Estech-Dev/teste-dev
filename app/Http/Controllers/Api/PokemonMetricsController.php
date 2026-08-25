<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PokemonMetricsController extends Controller
{
    private const METRICS = [
        'hp',
        'attack',
        'defense',
        'special-attack',
        'special-defense',
        'speed',
    ];

    private const FIELDS = [
        'id' => 'pokemons.id',
        'pokemon_id' => 'pokemons.pokeapi_id',
        'name' => 'pokemons.name',
        'base_experience' => 'pokemons.base_experience',
        'height' => 'pokemons.height',
        'weight' => 'pokemons.weight',
        'metric_value' => 'pokemon_stats.base_stat',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metric' => ['sometimes', 'string', Rule::in(self::METRICS)],
            'field' => ['sometimes', 'string', Rule::in(array_keys(self::FIELDS))],
            'order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $metric = $validated['metric'] ?? 'hp';
        $field = $validated['field'] ?? 'name';
        $order = $validated['order'] ?? 'desc';
        $limit = (int) ($validated['limit'] ?? 10);
        $column = self::FIELDS[$field];

        $page = Pokemon::query()
            ->join('pokemon_stats', 'pokemon_stats.pokemon_id', '=', 'pokemons.id')
            ->join('stats', 'stats.id', '=', 'pokemon_stats.stat_id')
            ->where('stats.name', $metric)
            ->selectRaw("{$column} as {$field}")
            ->orderBy('pokemon_stats.base_stat', $order)
            ->orderBy('pokemons.name')
            ->paginate($limit)
            ->appends($request->query());

        return response()->json([
            'data' => $page->getCollection()
                ->map(fn (object $pokemon): array => [$field => $pokemon->{$field}])
                ->values(),
            'metric' => $metric,
            'meta' => [
                'field' => $field,
                'order' => $order,
                'ordered_by' => 'metric_value',
                'limit' => $limit,
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
        ]);
    }
}
