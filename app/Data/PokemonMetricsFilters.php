<?php

namespace App\Data;

final readonly class PokemonMetricsFilters
{
    public const DEFAULT_METRIC = 'hp';

    public const DEFAULT_FIELD = 'name';

    public const DEFAULT_ORDER = 'desc';

    public const DEFAULT_LIMIT = 10;

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

    public function __construct(
        public string $metric,
        public string $field,
        public string $order,
        public int $limit,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromArray(array $validated): self
    {
        return new self(
            metric: $validated['metric'] ?? self::DEFAULT_METRIC,
            field: $validated['field'] ?? self::DEFAULT_FIELD,
            order: $validated['order'] ?? self::DEFAULT_ORDER,
            limit: (int) ($validated['limit'] ?? self::DEFAULT_LIMIT),
        );
    }

    /**
     * @return list<string>
     */
    public static function metrics(): array
    {
        return self::METRICS;
    }

    /**
     * @return list<string>
     */
    public static function fields(): array
    {
        return array_keys(self::FIELDS);
    }

    public function selectedColumn(): string
    {
        return self::FIELDS[$this->field];
    }
}
