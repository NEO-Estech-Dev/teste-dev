<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Fields the caller may ask for in the ranking payload.
 *
 * Same contract as PokemonMetric: the request never names a column, it names a
 * case of this enum, and the enum decides what gets selected.
 */
enum PokemonField: string
{
    case Id = 'id';
    case Name = 'name';
    case Value = 'value';
    case Height = 'height';
    case Weight = 'weight';
    case BaseExperience = 'base_experience';
    case StatsTotal = 'stats_total';
    case SpriteUrl = 'sprite_url';
    case Types = 'types';

    /**
     * Default projection: the ranking answers "who" and "how much".
     *
     * @return list<self>
     */
    public static function defaults(): array
    {
        return [self::Name, self::Value];
    }

    /**
     * @return list<string>
     */
    public static function accepted(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @param  list<string>  $values
     * @return list<self>
     */
    public static function fromList(array $values): array
    {
        $fields = [];

        foreach ($values as $value) {
            $field = self::tryFrom(strtolower(trim($value)));

            if ($field !== null && ! in_array($field, $fields, true)) {
                $fields[] = $field;
            }
        }

        return $fields === [] ? self::defaults() : $fields;
    }

    /**
     * Column on `pokemons` backing this field, or null when the field is not a
     * plain column (the metric value, or the types relation).
     */
    public function column(): ?string
    {
        return match ($this) {
            self::Value, self::Types => null,
            default => 'pokemons.'.$this->value,
        };
    }

    public function isRelation(): bool
    {
        return $this === self::Types;
    }
}
