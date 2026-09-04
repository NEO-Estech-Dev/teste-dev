<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PokemonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read int $pokeapi_id
 * @property-read string $name
 * @property-read int $height
 * @property-read int $weight
 * @property-read int|null $base_experience
 * @property-read int $hp
 * @property-read int $attack
 * @property-read int $defense
 * @property-read int $special_attack
 * @property-read int $special_defense
 * @property-read int $speed
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 */
final class Pokemon extends Model
{
    /** @use HasFactory<PokemonFactory> */
    use HasFactory;

    protected $table = 'pokemons';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pokeapi_id' => 'integer',
            'height' => 'integer',
            'weight' => 'integer',
            'base_experience' => 'integer',
            'hp' => 'integer',
            'attack' => 'integer',
            'defense' => 'integer',
            'special_attack' => 'integer',
            'special_defense' => 'integer',
            'speed' => 'integer',
        ];
    }
}
