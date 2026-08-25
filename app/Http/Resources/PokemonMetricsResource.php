<?php

namespace App\Http\Resources;

use App\Data\PokemonMetricsFilters;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class PokemonMetricsResource extends JsonResource
{
    public function __construct(
        LengthAwarePaginator $resource,
        private readonly PokemonMetricsFilters $filters,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LengthAwarePaginator $page */
        $page = $this->resource;
        $field = $this->filters->field;

        return [
            'data' => $page->getCollection()
                ->map(fn (object $pokemon): array => [$field => $pokemon->{$field}])
                ->values(),
            'metric' => $this->filters->metric,
            'meta' => [
                'field' => $field,
                'order' => $this->filters->order,
                'ordered_by' => 'metric_value',
                'limit' => $this->filters->limit,
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
        ];
    }
}
