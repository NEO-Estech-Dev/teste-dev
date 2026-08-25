<?php

namespace App\Services;

use App\Models\Ability;
use App\Models\Pokemon;
use App\Models\Species;
use App\Models\Stat;
use App\Models\Type;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PokeApiIngestionService
{
    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @return array{processed:int, pages:int, failed:int}
     */
    public function ingest(?int $limit = null, int $offset = 0, int $chunk = 50, bool $fresh = false): array
    {
        if ($fresh) {
            $this->clearDomainTables();
        }

        $processed = 0;
        $pages = 0;
        $failed = 0;
        $nextOffset = $offset;
        $total = null;

        while ($limit === null || $processed < $limit) {
            $pageLimit = $limit === null ? $chunk : min($chunk, $limit - $processed);
            $page = $this->get('pokemon', [
                'limit' => $pageLimit,
                'offset' => $nextOffset,
            ]);

            $pages++;
            $total ??= (int) $page['count'];
            $results = $page['results'] ?? [];

            if ($results === []) {
                break;
            }

            foreach ($results as $result) {
                try {
                    $detail = $this->get($result['url']);
                    $this->persistPokemon($detail);
                    $processed++;
                } catch (\Throwable $exception) {
                    $failed++;

                    Log::error('Falha ao ingerir Pokémon da PokeAPI.', [
                        'name' => $result['name'] ?? null,
                        'url' => $result['url'] ?? null,
                        'exception' => $exception::class,
                        'message' => $exception->getMessage(),
                    ]);

                    throw $exception;
                }
            }

            $nextOffset += count($results);

            if ($limit === null && $nextOffset >= $total) {
                break;
            }
        }

        return [
            'processed' => $processed,
            'pages' => $pages,
            'failed' => $failed,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistPokemon(array $payload): Pokemon
    {
        return DB::transaction(function () use ($payload): Pokemon {
            $species = $this->persistSpecies($payload);

            /** @var Pokemon $pokemon */
            $pokemon = Pokemon::query()->updateOrCreate(
                ['pokeapi_id' => (int) $payload['id']],
                [
                    'species_id' => $species?->id,
                    'name' => $payload['name'],
                    'height' => (int) $payload['height'],
                    'weight' => (int) $payload['weight'],
                    'base_experience' => $payload['base_experience'] === null
                        ? null
                        : (int) $payload['base_experience'],
                ],
            );

            $this->syncStats($pokemon, $payload['stats'] ?? []);
            $this->syncTypes($pokemon, $payload['types'] ?? []);
            $this->syncAbilities($pokemon, $payload['abilities'] ?? []);

            return $pokemon;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistSpecies(array $payload): ?Species
    {
        $speciesData = $payload['species'] ?? null;

        if (! is_array($speciesData) || empty($speciesData['url'])) {
            return null;
        }

        $speciesPayload = $this->get($speciesData['url']);
        $pokeApiId = $this->resourceId($speciesData['url']);

        /** @var Species $species */
        $species = Species::query()->updateOrCreate(
            ['pokeapi_id' => $pokeApiId],
            [
                'name' => $speciesPayload['name'] ?? $speciesData['name'],
                'base_happiness' => $speciesPayload['base_happiness'] ?? null,
                'capture_rate' => $speciesPayload['capture_rate'] ?? null,
                'is_baby' => (bool) ($speciesPayload['is_baby'] ?? false),
                'is_legendary' => (bool) ($speciesPayload['is_legendary'] ?? false),
                'is_mythical' => (bool) ($speciesPayload['is_mythical'] ?? false),
            ],
        );

        return $species;
    }

    /**
     * @param  array<int, array<string, mixed>>  $stats
     */
    private function syncStats(Pokemon $pokemon, array $stats): void
    {
        $sync = [];

        foreach ($stats as $item) {
            $statData = $item['stat'] ?? [];
            $url = $statData['url'] ?? null;

            /** @var Stat $stat */
            $stat = Stat::query()->updateOrCreate(
                ['name' => $statData['name']],
                ['pokeapi_id' => $url ? $this->resourceId($url) : null],
            );

            $sync[$stat->id] = [
                'base_stat' => (int) $item['base_stat'],
                'effort' => (int) $item['effort'],
            ];
        }

        $pokemon->stats()->sync($sync);
    }

    /**
     * @param  array<int, array<string, mixed>>  $types
     */
    private function syncTypes(Pokemon $pokemon, array $types): void
    {
        $sync = [];

        foreach ($types as $item) {
            $typeData = $item['type'] ?? [];
            $url = $typeData['url'] ?? null;

            /** @var Type $type */
            $type = Type::query()->updateOrCreate(
                ['name' => $typeData['name']],
                ['pokeapi_id' => $url ? $this->resourceId($url) : null],
            );

            $sync[$type->id] = [
                'slot' => (int) $item['slot'],
            ];
        }

        $pokemon->types()->sync($sync);
    }

    /**
     * @param  array<int, array<string, mixed>>  $abilities
     */
    private function syncAbilities(Pokemon $pokemon, array $abilities): void
    {
        $sync = [];

        foreach ($abilities as $item) {
            $abilityData = $item['ability'] ?? [];
            $url = $abilityData['url'] ?? null;

            /** @var Ability $ability */
            $ability = Ability::query()->updateOrCreate(
                ['name' => $abilityData['name']],
                ['pokeapi_id' => $url ? $this->resourceId($url) : null],
            );

            $sync[$ability->id] = [
                'is_hidden' => (bool) $item['is_hidden'],
                'slot' => (int) $item['slot'],
            ];
        }

        $pokemon->abilities()->sync($sync);
    }

    /**
     * @param  array<string, int>  $query
     * @return array<string, mixed>
     */
    private function get(string $uri, array $query = []): array
    {
        $url = str_starts_with($uri, 'http')
            ? $uri
            : 'https://pokeapi.co/api/v2/'.ltrim($uri, '/');

        $response = $this->http
            ->timeout(15)
            ->retry(3, 250)
            ->get($url, $query);

        if (! $response->successful()) {
            throw new RuntimeException("PokeAPI retornou HTTP {$response->status()} para {$url}.");
        }

        return $response->json();
    }

    private function resourceId(string $url): int
    {
        $id = (int) Arr::last(array_values(array_filter(explode('/', $url))));

        if ($id <= 0) {
            throw new RuntimeException("Não foi possível extrair o ID da PokeAPI em {$url}.");
        }

        return $id;
    }

    private function clearDomainTables(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                'pokemon_ability',
                'abilities',
                'pokemon_type',
                'types',
                'pokemon_stats',
                'stats',
                'pokemons',
                'species',
            ] as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
