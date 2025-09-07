<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    /**
     * Get Pokemon metrics based on specified criteria
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Validar métrica
        $validMetrics = [
            'hp', 'attack', 'defense', 'special_attack', 
            'special_defense', 'speed', 'total_stats', 
            'height', 'weight', 'order', 'base_experience'
        ];

        $metric = $request->get('metric', 'total_stats');
        if (!in_array($metric, $validMetrics)) {
            return response()->json([
                'error' => 'Invalid metric. Valid metrics: ' . implode(', ', $validMetrics)
            ], 400);
        }

        // Parâmetros opcionais
        $limit = $request->get('limit', 10);
        $order = $request->get('order', 'desc'); // desc = melhores, asc = piores
        $attribute = $request->get('attribute', 'name'); // atributo específico para mostrar

        // Validar order
        if (!in_array($order, ['asc', 'desc'])) {
            return response()->json([
                'error' => 'Invalid order. Use "asc" for worst or "desc" for best'
            ], 400);
        }

        // Validar attribute
        $validAttributes = [
            'name', 'pokemon_id', 'height', 'weight', 'base_experience',
            'hp', 'attack', 'defense', 'special_attack', 'special_defense', 'speed', 'total_stats'
        ];
        
        if (!in_array($attribute, $validAttributes)) {
            return response()->json([
                'error' => 'Invalid attribute. Valid attributes: ' . implode(', ', $validAttributes)
            ], 400);
        }

        // Construir query
        $query = Pokemon::query();

        // Filtrar apenas pokémons com stats (se for uma métrica de stats)
        if (in_array($metric, ['hp', 'attack', 'defense', 'special_attack', 'special_defense', 'speed', 'total_stats'])) {
            $query->whereNotNull($metric);
        }

        // Ordenar pela métrica
        $query->orderBy($metric, $order);

        // Limitar resultados
        $query->limit($limit);

        // Selecionar apenas os campos necessários
        $pokemon = $query->select([
            'id', 'name', 'pokemon_id', 'height', 'weight', 'base_experience',
            'hp', 'attack', 'defense', 'special_attack', 'special_defense', 'speed', 'total_stats'
        ])->get();

        // Formatar resposta
        $result = [
            'metric' => $metric,
            'order' => $order,
            'limit' => $limit,
            'attribute' => $attribute,
            'total_found' => $pokemon->count(),
            'data' => []
        ];

        foreach ($pokemon as $poke) {
            $item = [
                'pokemon_id' => $poke->pokemon_id,
                'name' => $poke->name,
                'metric_value' => $poke->{$metric},
            ];

            // Adicionar atributo específico se solicitado
            if ($attribute !== 'name') {
                $item[$attribute] = $poke->{$attribute};
            }

            // Adicionar todos os stats se a métrica for total_stats
            if ($metric === 'total_stats') {
                $item['stats'] = [
                    'hp' => $poke->hp,
                    'attack' => $poke->attack,
                    'defense' => $poke->defense,
                    'special_attack' => $poke->special_attack,
                    'special_defense' => $poke->special_defense,
                    'speed' => $poke->speed,
                ];
            }

            $result['data'][] = $item;
        }

        return response()->json($result);
    }

    /**
     * Get available metrics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableMetrics()
    {
        return response()->json([
            'metrics' => [
                'hp' => 'Hit Points (HP)',
                'attack' => 'Attack Power',
                'defense' => 'Defense Power',
                'special_attack' => 'Special Attack Power',
                'special_defense' => 'Special Defense Power',
                'speed' => 'Speed',
                'total_stats' => 'Total Stats (sum of all stats)',
                'height' => 'Height',
                'weight' => 'Weight',
                'order' => 'Pokemon Order (Pokedex number)',
                'base_experience' => 'Base Experience'
            ],
            'attributes' => [
                'name' => 'Pokemon Name',
                'pokemon_id' => 'Pokemon ID from PokeAPI',
                'height' => 'Height',
                'weight' => 'Weight',
                'base_experience' => 'Base Experience',
                'hp' => 'Hit Points',
                'attack' => 'Attack Power',
                'defense' => 'Defense Power',
                'special_attack' => 'Special Attack Power',
                'special_defense' => 'Special Defense Power',
                'speed' => 'Speed',
                'total_stats' => 'Total Stats'
            ],
            'orders' => [
                'desc' => 'Best (highest values first)',
                'asc' => 'Worst (lowest values first)'
            ]
        ]);
    }
}
