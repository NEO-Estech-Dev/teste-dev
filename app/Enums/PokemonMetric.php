<?php

declare(strict_types=1);

namespace App\Enums;

enum PokemonMetric: string
{
    case Hp = 'hp';
    case Attack = 'attack';
    case Defense = 'defense';
    case SpecialAttack = 'special_attack';
    case SpecialDefense = 'special_defense';
    case Speed = 'speed';
    case Height = 'height';
    case Weight = 'weight';
    case BaseExperience = 'base_experience';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $metric): string => $metric->value,
            self::cases(),
        );
    }

    public function column(): string
    {
        return match ($this) {
            self::Hp => 'hp',
            self::Attack => 'attack',
            self::Defense => 'defense',
            self::SpecialAttack => 'special_attack',
            self::SpecialDefense => 'special_defense',
            self::Speed => 'speed',
            self::Height => 'height',
            self::Weight => 'weight',
            self::BaseExperience => 'base_experience',
        };
    }
}
