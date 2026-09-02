<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PokemonMetricController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $fields = [
            'name',
            'pokeapi_id',
            'height',
            'weight',
            'base_experience',
            'sprite_url',
        ];

        $validated = $request->validate([
            'metric' => ['sometimes', 'string'],
            'field' => ['sometimes', Rule::in($fields)],
            'order' => ['sometimes', Rule::in(['asc', 'desc'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $metric = $validated['metric'] ?? 'hp';
        $field = $validated['field'] ?? 'name';
        $order = $validated['order'] ?? 'desc';
        $limit = $validated['limit'] ?? 10;

        $results = DB::table('pokemons')
            ->join('pokemon_stats', 'pokemons.id', '=', 'pokemon_stats.pokemon_id')
            ->where('pokemon_stats.stat_name', $metric)
            ->orderBy('pokemon_stats.base_value', $order)
            ->limit($limit)
            ->select([
                "pokemons.{$field} as {$field}",
                'pokemon_stats.base_value',
            ])
            ->get();

        return response()->json([
            'metric' => $metric,
            'field' => $field,
            'order' => $order,
            'limit' => $limit,
            'results' => $results,
        ]);
    }
}
