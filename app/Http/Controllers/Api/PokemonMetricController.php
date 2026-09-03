<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MetricsRequest;
use App\Models\Pokemon;
use Illuminate\Http\JsonResponse;

class PokemonMetricController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(MetricsRequest $request): JsonResponse
    {
        $input = $request->validated();
        $metric = $input['metric'] ?? 'hp';
        $direction = $input['direction'] ?? 'desc';
        $fields = array_values(array_unique(array_map('trim', explode(',', $input['fields'] ?? 'name,metric'))));
        $columns = array_map(
            fn (string $field): string => $field === 'metric' ? "{$metric} as metric" : $field,
            $fields,
        );

        $paginator = Pokemon::query()
            ->select($columns)
            ->orderBy($metric, $direction)
            ->orderBy('id', $direction)
            ->paginate($input['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'metric' => $metric,
                'direction' => $direction,
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
