<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Every metric the API can rank by.
 *
 * This enum is the only place a metric name is turned into a database column.
 * Nothing coming from the request ever reaches orderBy() directly, so the
 * dynamic ordering cannot be used to inject SQL.
 */
enum PokemonMetric: string
{
    // Stored as rows in `pokemon_stats`, using the PokeAPI slug.
    case Hp = 'hp';
    case Attack = 'attack';
    case Defense = 'defense';
    case SpecialAttack = 'special-attack';
    case SpecialDefense = 'special-defense';
    case Speed = 'speed';

    // Stored as columns on `pokemons`.
    case Height = 'height';
    case Weight = 'weight';
    case BaseExperience = 'base_experience';
    case StatsTotal = 'stats_total';

    /**
     * Alternative spellings accepted on input, mapped to the canonical value.
     *
     * The PokeAPI uses hyphens ("special-attack"), which is awkward in a query
     * string, so the underscore form is accepted too.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'special_attack' => 'special-attack',
        'special_defense' => 'special-defense',
    ];

    public static function default(): self
    {
        return self::Hp;
    }

    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        return self::ALIASES[$value] ?? $value;
    }

    public static function tryFromInput(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom(self::normalize($value));
    }

    /**
     * Every string accepted by the `metric` parameter, canonical plus aliases.
     *
     * @return list<string>
     */
    public static function accepted(): array
    {
        return array_values(array_unique([
            ...array_column(self::cases(), 'value'),
            ...array_keys(self::ALIASES),
        ]));
    }

    /**
     * Whether the metric lives in `pokemon_stats` (as opposed to `pokemons`).
     */
    public function isStat(): bool
    {
        return match ($this) {
            self::Hp,
            self::Attack,
            self::Defense,
            self::SpecialAttack,
            self::SpecialDefense,
            self::Speed => true,
            default => false,
        };
    }

    /**
     * Fully qualified column holding this metric's value.
     */
    public function column(): string
    {
        return $this->isStat()
            ? 'pokemon_stats.base_stat'
            : 'pokemons.'.$this->value;
    }

    /**
     * Metrics that may be NULL must be filtered out of a ranking, otherwise an
     * ascending order returns a page of empty values instead of the lowest ones.
     */
    public function isNullable(): bool
    {
        return $this === self::BaseExperience;
    }

    public function label(): string
    {
        return str_replace(['-', '_'], ' ', $this->value);
    }
}
