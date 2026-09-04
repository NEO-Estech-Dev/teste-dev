<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Integrations\PokeApi\DataObjects\PokemonDetails;
use App\Models\Pokemon;
use Illuminate\Support\Collection;

final readonly class UpsertPokemon
{
    /**
     * @param  Collection<int, PokemonDetails>  $pokemons
     */
    public function handle(Collection $pokemons): void
    {
        if ($pokemons->isEmpty()) {
            return;
        }

        $timestamp = now();
        $rows = $pokemons->map(static fn (PokemonDetails $pokemon): array => [
            'pokeapi_id' => $pokemon->pokeapiId,
            'name' => $pokemon->name,
            'height' => $pokemon->height,
            'weight' => $pokemon->weight,
            'base_experience' => $pokemon->baseExperience,
            'hp' => $pokemon->hp,
            'attack' => $pokemon->attack,
            'defense' => $pokemon->defense,
            'special_attack' => $pokemon->specialAttack,
            'special_defense' => $pokemon->specialDefense,
            'speed' => $pokemon->speed,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->all();

        Pokemon::upsert(
            $rows,
            ['pokeapi_id'],
            [
                'name',
                'height',
                'weight',
                'base_experience',
                'hp',
                'attack',
                'defense',
                'special_attack',
                'special_defense',
                'speed',
                'updated_at',
            ],
        );
    }
}
