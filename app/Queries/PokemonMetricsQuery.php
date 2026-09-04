<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\MetricOrder;
use App\Enums\PokemonField;
use App\Enums\PokemonMetric;
use App\Models\Pokemon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Builds the ranking query.
 *
 * Column names come from the enums, never from the request, and only the
 * columns actually asked for are selected.
 */
final class PokemonMetricsQuery
{
    /**
     * @param  list<PokemonField>  $fields
     */
    public function __construct(
        private readonly PokemonMetric $metric,
        private readonly MetricOrder $order,
        private readonly array $fields,
        private readonly ?string $type = null,
        private readonly bool $onlyDefault = false,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Pokemon>
     */
    public function paginate(int $perPage, int $page): LengthAwarePaginator
    {
        return $this->builder()->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @return Builder<Pokemon>
     */
    public function builder(): Builder
    {
        $query = Pokemon::query()->select($this->selectColumns());

        if ($this->metric->isStat()) {
            // Constraining the stat inside the JOIN keeps the (stat, base_stat)
            // index usable for both the filter and the ordering.
            $query->join('pokemon_stats', function (JoinClause $join): void {
                $join->on('pokemon_stats.pokemon_id', '=', 'pokemons.id')
                    ->where('pokemon_stats.stat', '=', $this->metric->value);
            });
        }

        if ($this->metric->isNullable()) {
            // Without this an ascending ranking would return rows with no value
            // at all instead of the actual lowest ones.
            $query->whereNotNull($this->metric->column());
        }

        if ($this->onlyDefault) {
            $query->where('pokemons.is_default', true);
        }

        if ($this->type !== null) {
            $query->whereHas('types', fn (Builder $types) => $types->where('types.name', $this->type));
        }

        if ($this->wantsTypes()) {
            $query->with(['types' => fn ($types) => $types->select('types.id', 'types.name')]);
        }

        return $query
            ->orderBy($this->metric->column(), $this->order->direction())
            // Deterministic tie-break: without it, equal values would paginate
            // inconsistently. Matching directions lets MySQL scan the ranking
            // index forward or backward without an extra sort.
            ->orderBy($this->tieBreakerColumn(), $this->order->direction());
    }

    /**
     * @return list<string>
     */
    private function selectColumns(): array
    {
        // The key is always selected: Eloquent needs it to hydrate the model
        // and to eager load the types relation.
        $columns = ['pokemons.id'];

        foreach ($this->fields as $field) {
            $column = $field->column();

            if ($column !== null) {
                $columns[] = $column;
            }
        }

        // The metric value is exposed under a stable `value` alias, whether it
        // comes from a stat row or from a column on `pokemons`.
        $columns[] = $this->metric->column().' as value';

        return array_values(array_unique($columns));
    }

    private function wantsTypes(): bool
    {
        foreach ($this->fields as $field) {
            if ($field->isRelation()) {
                return true;
            }
        }

        return false;
    }

    private function tieBreakerColumn(): string
    {
        return $this->metric->isStat()
            ? 'pokemon_stats.pokemon_id'
            : 'pokemons.id';
    }
}
