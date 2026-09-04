<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Type extends Model
{
    protected $table = 'types';

    /**
     * @return BelongsToMany<Pokemon, $this>
     */
    public function pokemons(): BelongsToMany
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_type')
            ->withPivot('slot');
    }
}
