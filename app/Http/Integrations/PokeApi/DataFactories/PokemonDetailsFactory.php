<?php

declare(strict_types=1);

namespace App\Http\Integrations\PokeApi\DataFactories;

use App\Http\Integrations\PokeApi\DataObjects\PokemonDetails;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final readonly class PokemonDetailsFactory
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function make(array $attributes): PokemonDetails
    {
        $stats = [];

        foreach (Arr::array($attributes, 'stats') as $stat) {
            $stat = Arr::wrap($stat);
            $stats[Arr::string($stat, 'stat.name')] = Arr::integer($stat, 'base_stat');
        }

        $baseExperience = $attributes['base_experience'] ?? null;

        return new PokemonDetails(
            pokeapiId: Arr::integer($attributes, 'id'),
            name: Arr::string($attributes, 'name'),
            height: Arr::integer($attributes, 'height'),
            weight: Arr::integer($attributes, 'weight'),
            baseExperience: $baseExperience === null
                ? null
                : Arr::integer($attributes, 'base_experience'),
            hp: $stats['hp'],
            attack: $stats['attack'],
            defense: $stats['defense'],
            specialAttack: $stats['special-attack'],
            specialDefense: $stats['special-defense'],
            speed: $stats['speed'],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, PokemonDetails>
     */
    public function collection(array $items): Collection
    {
        return collect($items)
            ->values()
            ->map(fn (array $attributes): PokemonDetails => $this->make($attributes));
    }
}
