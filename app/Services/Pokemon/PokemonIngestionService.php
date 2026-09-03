<?php

namespace App\Services\Pokemon;

use App\Exceptions\PokeApiException;
use App\Models\Pokemon;
use App\Models\Type;
use App\Services\PokeApi\PokeApiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class PokemonIngestionService
{
    public function __construct(private readonly PokeApiClient $client) {}

    /**
     * @return array{imported: int, failed: int, limit: int, offset: int, errors: list<array{name: string, error: string}>}
     */
    public function ingest(
        int $limit,
        int $offset = 0,
        bool $fresh = false,
        int $concurrency = 10,
        ?callable $onProgress = null
    ): array {
        if ($fresh) {
            $this->resetCatalog();
        }

        $listing = $this->client->listPokemons($limit, $offset);
        $results = $listing['results'] ?? [];
        $imported = 0;
        $errors = [];
        $concurrency = max(1, min($concurrency, 25));

        foreach (array_chunk($results, $concurrency) as $chunk) {
            $names = array_values(array_map(
                fn (array $item): string => (string) ($item['name'] ?? 'unknown'),
                $chunk
            ));

            $payloads = $this->client->getPokemons($names);

            foreach ($names as $name) {
                try {
                    $payload = $payloads[$name] ?? new PokeApiException("Falha ao obter o Pokémon [{$name}] na PokeAPI.");

                    if ($payload instanceof Throwable) {
                        throw $payload;
                    }

                    $this->persist($payload);
                    $imported++;
                } catch (Throwable $exception) {
                    $errors[] = [
                        'name' => $name,
                        'error' => $exception->getMessage(),
                    ];

                    Log::warning('Falha ao importar Pokémon.', [
                        'name' => $name,
                        'message' => $exception->getMessage(),
                    ]);
                }

                if ($onProgress) {
                    $onProgress($name);
                }
            }
        }

        return [
            'imported' => $imported,
            'failed' => count($errors),
            'limit' => $limit,
            'offset' => $offset,
            'errors' => $errors,
        ];
    }

    private function resetCatalog(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('pokemon_type')->delete();
        Pokemon::query()->delete();
        Type::query()->delete();
        Schema::enableForeignKeyConstraints();
    }

    private function persist(array $payload): Pokemon
    {
        return DB::transaction(function () use ($payload) {
            $stats = $this->mapStats($payload['stats'] ?? []);

            $pokemon = Pokemon::query()->updateOrCreate(
                ['pokeapi_id' => $payload['id']],
                [
                    'name' => $payload['name'],
                    'height' => $payload['height'] ?? null,
                    'weight' => $payload['weight'] ?? null,
                    'hp' => $stats['hp'],
                    'attack' => $stats['attack'],
                    'defense' => $stats['defense'],
                    'special_attack' => $stats['special_attack'],
                    'special_defense' => $stats['special_defense'],
                    'speed' => $stats['speed'],
                    'sprite_url' => data_get($payload, 'sprites.front_default'),
                ]
            );

            $typeIds = [];

            foreach ($payload['types'] ?? [] as $typeData) {
                $typeName = data_get($typeData, 'type.name');
                $typeUrl = data_get($typeData, 'type.url');

                if (! $typeName || ! $typeUrl) {
                    continue;
                }

                $type = Type::query()->updateOrCreate(
                    ['name' => $typeName],
                    ['pokeapi_id' => $this->extractIdFromUrl($typeUrl)]
                );

                $typeIds[$type->id] = ['slot' => $typeData['slot'] ?? 1];
            }

            $pokemon->types()->sync($typeIds);

            return $pokemon;
        });
    }

    /**
     * @return array{hp: int, attack: int, defense: int, special_attack: int, special_defense: int, speed: int}
     */
    private function mapStats(array $stats): array
    {
        $mapped = [
            'hp' => 0,
            'attack' => 0,
            'defense' => 0,
            'special_attack' => 0,
            'special_defense' => 0,
            'speed' => 0,
        ];

        foreach ($stats as $stat) {
            $key = Str::of((string) data_get($stat, 'stat.name'))->replace('-', '_')->toString();

            if (array_key_exists($key, $mapped)) {
                $mapped[$key] = (int) ($stat['base_stat'] ?? 0);
            }
        }

        return $mapped;
    }

    private function extractIdFromUrl(string $url): int
    {
        preg_match('/\/(\d+)\/?$/', $url, $matches);

        return (int) ($matches[1] ?? 0);
    }
}
