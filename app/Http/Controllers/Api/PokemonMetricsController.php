<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PokemonMetricsRequest;
use App\Http\Resources\PokemonMetricResource;
use App\Models\Pokemon;
use App\Queries\PokemonMetricsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final class PokemonMetricsController extends Controller
{
    public function __invoke(PokemonMetricsRequest $request): JsonResponse
    {
        $metric = $request->metric();
        $order = $request->order();
        $fields = $request->fields();

        $cacheKey = 'pokemon_metrics:'.hash('sha256', (string) json_encode($request->validated()));

        $payload = Cache::tags([(string) config('metrics.cache_tag')])->remember(
            $cacheKey,
            (int) config('metrics.cache_ttl'),
            function () use ($request, $metric, $order, $fields): array {
                $paginator = (new PokemonMetricsQuery(
                    metric: $metric,
                    order: $order,
                    fields: $fields,
                    type: $request->typeFilter(),
                    onlyDefault: $request->onlyDefault(),
                ))->paginate($request->perPage(), $request->page());

                return [
                    'data' => array_map(
                        fn (Pokemon $pokemon): array => (new PokemonMetricResource($pokemon, $fields))->toArray($request),
                        $paginator->items(),
                    ),
                    'meta' => [
                        'metric' => $metric->value,
                        'order' => $order->value,
                        'limit' => $paginator->perPage(),
                        'page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'total' => $paginator->total(),
                        'type' => $request->typeFilter(),
                        'only_default' => $request->onlyDefault(),
                    ],
                ];
            },
        );

        return response()->json($payload);
    }
}
