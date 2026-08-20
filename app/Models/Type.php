<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Type extends Model
{
    protected $fillable = [
        'pokeapi_id',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'pokeapi_id' => 'integer',
        ];
    }

    public function pokemons(): BelongsToMany
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_type')
            ->withPivot('slot');
    }
}
