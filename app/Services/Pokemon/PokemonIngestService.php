<?php

declare(strict_types=1);

namespace App\Services\Pokemon;

use App\DTOs\PokemonData;
use App\Models\Pokemon;
use App\Models\PokemonStat;
use App\Models\Type;
use Illuminate\Support\Facades\DB;

/**
 * Writes a batch of PokemonData into the database.
 *
 * Everything is an upsert against a natural key, so running the ingestion
 * twice converges on the same rows instead of duplicating them.
 */
final class PokemonIngestService
{
    /**
     * @param  list<PokemonData>  $pokemon
     */
    public function storeChunk(array $pokemon): void
    {
        if ($pokemon === []) {
            return;
        }

        DB::transaction(function () use ($pokemon): void {
            Pokemon::upsert(
                array_map(static fn (PokemonData $data): array => $data->toRow(), $pokemon),
                ['id'],
                ['name', 'height', 'weight', 'base_experience', 'order', 'is_default', 'sprite_url', 'stats_total'],
            );

            $statRows = [];
            $pokemonIds = [];

            foreach ($pokemon as $data) {
                $pokemonIds[] = $data->id;

                foreach ($data->statRows() as $row) {
                    $statRows[] = $row;
                }
            }

            PokemonStat::query()->whereIn('pokemon_id', $pokemonIds)->delete();

            if ($statRows !== []) {
                PokemonStat::upsert($statRows, ['pokemon_id', 'stat'], ['base_stat', 'effort']);
            }

            $this->syncTypes($pokemon);
        });
    }

    /**
     * Remove every ingested row, in foreign key order.
     */
    public function truncate(): void
    {
        DB::transaction(static function (): void {
            DB::table('pokemon_type')->delete();
            DB::table('pokemon_stats')->delete();
            DB::table('pokemons')->delete();
            DB::table('types')->delete();
        });
    }

    /**
     * @param  list<PokemonData>  $pokemon
     */
    private function syncTypes(array $pokemon): void
    {
        $names = [];
        $pokemonIds = [];

        foreach ($pokemon as $data) {
            $pokemonIds[] = $data->id;

            foreach ($data->types as $type) {
                $names[$type['name']] = true;
            }
        }

        DB::table('pokemon_type')->whereIn('pokemon_id', $pokemonIds)->delete();

        $names = array_keys($names);

        if ($names === []) {
            return;
        }

        Type::upsert(
            array_map(static fn (string $name): array => ['name' => $name], $names),
            ['name'],
            ['name'],
        );

        /** @var array<string, int> $typeIds */
        $typeIds = Type::query()->whereIn('name', $names)->pluck('id', 'name')->all();

        $pivotRows = [];

        foreach ($pokemon as $data) {
            foreach ($data->types as $type) {
                if (! isset($typeIds[$type['name']])) {
                    continue;
                }

                $pivotRows[] = [
                    'pokemon_id' => $data->id,
                    'type_id' => $typeIds[$type['name']],
                    'slot' => $type['slot'],
                ];
            }
        }

        if ($pivotRows !== []) {
            DB::table('pokemon_type')->upsert($pivotRows, ['pokemon_id', 'type_id'], ['slot']);
        }
    }
}
