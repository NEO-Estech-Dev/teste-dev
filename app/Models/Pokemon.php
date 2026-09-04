<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PokemonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'id',
    'name',
    'height',
    'weight',
    'base_experience',
    'order',
    'is_default',
    'sprite_url',
    'stats_total',
])]
class Pokemon extends Model
{
    /** @use HasFactory<PokemonFactory> */
    use HasFactory;

    protected $table = 'pokemons';

    // The primary key is the PokeAPI id, not a generated sequence.
    public $incrementing = false;

    protected $keyType = 'int';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PokemonStat, $this>
     */
    public function stats(): HasMany
    {
        return $this->hasMany(PokemonStat::class);
    }

    /**
     * @return BelongsToMany<Type, $this>
     */
    public function types(): BelongsToMany
    {
        return $this->belongsToMany(Type::class, 'pokemon_type')
            ->withPivot('slot')
            ->orderBy('pokemon_type.slot');
    }
}
