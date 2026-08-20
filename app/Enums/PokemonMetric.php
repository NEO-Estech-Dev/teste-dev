<?php

namespace App\Enums;

enum PokemonMetric: string
{
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
        return array_column(self::cases(), 'value');
    }
}
