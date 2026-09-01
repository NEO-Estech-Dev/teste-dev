<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\PokemonMetricRequest;
use App\Models\Pokemon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PokemonMetricController extends Controller
{
    public function index(PokemonMetricRequest $request): JsonResponse
    {
        // 1. Pega os dados validados e define valores padrão coerentes
        $metric  = $request->validated('metric', 'hp'); // Padrão: hp
        $sort    = $request->validated('sort', 'desc'); // Padrão: desc (Maiores/Melhores primeiro)
        $perPage = $request->validated('per_page', 15);
        $field   = $request->validated('field');

        // 2. Cria uma chave única considerando os parâmetros e os defaults
        $cacheKey = 'pokemons_metrics_' . md5(json_encode([$metric, $sort, $perPage, $field]));

        // 3. Busca do cache ou executa a query e guarda por 1 hora (3600 segundos)
        $results = Cache::remember($cacheKey, 3600, function () use ($field, $metric, $sort, $perPage) {
            $query = Pokemon::query();

            // Se o usuário pediu apenas um campo específico
            if ($field) {
                $query->select('id', $field, $metric);
            }

            // Aplica a ordenação
            $query->orderBy($metric, $sort);

            return $query->paginate($perPage);
        });

        // 4. Retorna em formato JSON nativo com paginação
        return response()->json($results);
    }
}
