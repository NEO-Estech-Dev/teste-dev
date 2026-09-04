<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pokemon_id', 'stat', 'base_stat', 'effort'])]
class PokemonStat extends Model
{
    protected $table = 'pokemon_stats';

    /**
     * @return BelongsTo<Pokemon, $this>
     */
    public function pokemon(): BelongsTo
    {
        return $this->belongsTo(Pokemon::class);
    }
}
