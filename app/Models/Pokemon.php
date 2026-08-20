<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('pokemons')]
#[Fillable(['name', 'external_id', 'external_url', 'processed_at'])]
class Pokemon extends Model
{
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function stats(): HasMany
    {
        return $this->hasMany(PokemonStat::class);
    }

    public static function getMetricRanking(String $metric, String $sort, Int $limit)
    {
        return self::query()
                ->select([
                    'pokemons.name',
                ])
                ->join(
                    'pokemons_stats',
                    'pokemons.id',
                    '=',
                    'pokemons_stats.pokemon_id'
                )
                ->where('pokemons_stats.stat_name', $metric)
                ->orderBy('pokemons_stats.base_stat', $sort)
                ->limit($limit)
                ->get();
    }
}
