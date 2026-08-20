<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pokemons_stats')]
#[Fillable(['pokemon_id', 'stat_name', 'base_stat', 'effort'])]
class PokemonStat extends Model
{
    protected function casts(): array
    {
        return [
            'base_stat' => 'integer',
            'effort' => 'integer',
        ];
    }

    public function pokemon(): BelongsTo
    {
        return $this->belongsTo(Pokemon::class);
    }
}