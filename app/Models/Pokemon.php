<?php

namespace App\Models;

use Database\Factories\PokemonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'pokeapi_id',
    'name',
    'height',
    'weight',
    'base_experience',
    'types',
    'hp',
    'attack',
    'defense',
    'special_attack',
    'special_defense',
    'speed',
])]
class Pokemon extends Model
{
    /** @use HasFactory<PokemonFactory> */
    use HasFactory;

    protected $table = 'pokemon';

    protected function casts(): array
    {
        return [
            'types' => 'array',
        ];
    }
}
