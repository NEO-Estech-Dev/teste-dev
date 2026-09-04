<?php

declare(strict_types=1);

namespace App\Enums;

enum PokemonField: string
{
    case PokeapiId = 'pokeapi_id';
    case Name = 'name';
    case Height = 'height';
    case Weight = 'weight';
    case BaseExperience = 'base_experience';
    case Hp = 'hp';
    case Attack = 'attack';
    case Defense = 'defense';
    case SpecialAttack = 'special_attack';
    case SpecialDefense = 'special_defense';
    case Speed = 'speed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $field): string => $field->value,
            self::cases(),
        );
    }

    public function column(): string
    {
        return match ($this) {
            self::PokeapiId => 'pokeapi_id',
            self::Name => 'name',
            self::Height => 'height',
            self::Weight => 'weight',
            self::BaseExperience => 'base_experience',
            self::Hp => 'hp',
            self::Attack => 'attack',
            self::Defense => 'defense',
            self::SpecialAttack => 'special_attack',
            self::SpecialDefense => 'special_defense',
            self::Speed => 'speed',
        };
    }
}
