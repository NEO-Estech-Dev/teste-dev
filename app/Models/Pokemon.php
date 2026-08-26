<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'pokeapi_id',
    'species_id',
    'name',
    'height',
    'weight',
    'base_experience',
])]
class Pokemon extends Model
{
    use HasFactory;

    protected $table = 'pokemons';

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function stats(): BelongsToMany
    {
        return $this->belongsToMany(Stat::class, 'pokemon_stats')
            ->withPivot(['base_stat', 'effort'])
            ->withTimestamps();
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(Type::class, 'pokemon_type')
            ->withPivot('slot')
            ->withTimestamps();
    }

    public function abilities(): BelongsToMany
    {
        return $this->belongsToMany(Ability::class, 'pokemon_ability')
            ->withPivot(['is_hidden', 'slot'])
            ->withTimestamps();
    }
}
