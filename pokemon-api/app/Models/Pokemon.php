<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pokemon extends Model
{
    use HasFactory;

    protected $table = 'pokemons';

    protected $fillable = [
        'pokeapi_id',
        'name',
        'height',
        'weight',
        'base_experience',
        'sprite_url',
    ];

    public function stats(): HasMany
    {
        return $this->hasMany(PokemonStat::class);
    }
}
