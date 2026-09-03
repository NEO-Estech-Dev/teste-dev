<?php

namespace App\Models;

use Database\Factories\PokemonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pokemon extends Model
{
    /** @use HasFactory<PokemonFactory> */
    use HasFactory;

    protected $table = 'pokemons';

    protected $fillable = [
        'pokeapi_id',
        'name',
        'height',
        'weight',
        'hp',
        'attack',
        'defense',
        'special_attack',
        'special_defense',
        'speed',
        'sprite_url',
    ];

    protected function casts(): array
    {
        return [
            'pokeapi_id' => 'integer',
            'height' => 'integer',
            'weight' => 'integer',
            'hp' => 'integer',
            'attack' => 'integer',
            'defense' => 'integer',
            'special_attack' => 'integer',
            'special_defense' => 'integer',
            'speed' => 'integer',
        ];
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(Type::class, 'pokemon_type')
            ->withPivot('slot')
            ->orderByPivot('slot');
    }
}
