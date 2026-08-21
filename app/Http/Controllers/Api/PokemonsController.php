<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PokemonsController extends Controller
{
    public function metrics(Request $request)
    {
        $validated = $this->validateQueryStrings($request);

        $metric = Str::lower($validated['metric'] ?? 'hp');
        $sort = Str::lower($validated['sort'] ?? 'desc');
        $limit = $validated['limit'] ?? 20;

        $data = Pokemon::getMetricRanking($metric, $sort, $limit);

        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'Sucesso',
                'message' => 'Nenhum Pokémon encontrado.',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'Sucesso',
            'message' => 'API de métricas dos Pokémons',
            'data' => $data,
        ]);
    }

    public function validateQueryStrings(Request $request)
    {
        if ($request->query->count() === 0) {
            return response()->json([
                'status' => 'Erro',
                'message' => 'API de métricas dos Pokémons',
                'parameters' => [
                    'metric' => [
                        'description' => 'Métrica utilizada para classificar os Pokémons.',
                        'required' => 'Sim',
                        'allowed' => [
                            'hp',
                            'speed',
                            'attack',
                            'special-attack',
                            'defense',
                            'special-defense',
                        ],
                        'default' => 'hp',
                    ],
                    'sort' => [
                        'description' => 'Ordem de classificação do ranking.',
                        'required' => 'Não',
                        'allowed' => [
                            'asc',
                            'desc',
                        ],
                        'default' => 'desc',
                    ],
                    'limit' => [
                        'description' => 'Quantidade de Pokémons retornados.',
                        'required' => 'Não',
                        'allowed' => '1-100',
                        'default' => 20,
                    ],
                ],
                'examples' => [
                    '/api/pokemons/metrics?metric=hp',
                    '/api/pokemons/metrics?metric=attack&sort=asc',
                    '/api/pokemons/metrics?metric=speed&sort=desc&limit=10',
                ],
            ]);
        }

        return $request->validate(
            [
                'metric' => [
                    'required',
                    'in:hp,speed,attack,special-attack,defense,special-defense',
                ],
                'sort' => [
                    'sometimes',
                    'in:asc,desc',
                ],
                'limit' => [
                    'sometimes',
                    'integer',
                    'min:1',
                    'max:100',
                ],
            ],
            [
                'metric.required' => 'O campo metric é obrigatório. Exemplo: ?metric=hp',
                'metric.in' => 'A métrica deve ser uma das seguintes: hp, speed, attack, special-attack, defense, special-defense.',
                'sort.in' => 'A ordenação deve ser asc ou desc. Exemplo: ?metric=hp&sort=desc ou ?metric=hp&limit=20&sort=desc',
                'limit.integer' => 'O limite deve ser um número inteiro. Exemplo: ?metric=hp&limit=20 ou ?metric=hp&limit=20&sort=desc',
                'limit.min' => 'O limite deve ser de pelo menos 1.',
                'limit.max' => 'O limite não pode ser maior que 100.',
            ]
        );
    }
}
