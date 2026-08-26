<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['pokeapi_id', 'name'])]
class Ability extends Model
{
    use HasFactory;

    public function pokemons(): BelongsToMany
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_ability')
            ->withPivot(['is_hidden', 'slot'])
            ->withTimestamps();
    }
}
