<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'pokeapi_id',
    'name',
    'base_happiness',
    'capture_rate',
    'is_baby',
    'is_legendary',
    'is_mythical',
])]
class Species extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_baby' => 'boolean',
            'is_legendary' => 'boolean',
            'is_mythical' => 'boolean',
        ];
    }

    public function pokemons(): HasMany
    {
        return $this->hasMany(Pokemon::class);
    }
}
