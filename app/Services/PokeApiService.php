<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PokeApiService
{
    public function __construct(private readonly string $baseUrl = '') {}

    public function count(): int
    {
        return (int) $this->request()->get($this->url('pokemon'), ['limit' => 1])->throw()->json('count');
    }

    /** @return list<array{name: string, url: string}> */
    public function page(int $offset, int $limit): array
    {
        return $this->request()
            ->get($this->url('pokemon'), compact('offset', 'limit'))
            ->throw()
            ->json('results', []);
    }

    /**
     * @param  list<array{name: string, url: string}>  $pokemon
     * @return list<array<string, mixed>>
     */
    public function details(array $pokemon, int $concurrency): array
    {
        $responses = Http::pool(
            fn (Pool $pool): array => array_map(
                fn (array $item) => $pool->as($item['name'])
                    ->acceptJson()
                    ->retry(3, 250)
                    ->timeout(20)
                    ->get($item['url']),
                $pokemon,
            ),
            $concurrency,
        );

        return array_values(array_map(function (Response|\Throwable $response, string $name): array {
            if ($response instanceof \Throwable) {
                throw new RuntimeException("Falha ao consultar {$name}: {$response->getMessage()}", previous: $response);
            }

            return $this->transform($response->throw()->json());
        }, $responses, array_keys($responses)));
    }

    /** @param array<string, mixed> $payload */
    public function transform(array $payload): array
    {
        $stats = collect($payload['stats'] ?? [])->mapWithKeys(
            fn (array $stat): array => [$stat['stat']['name'] => $stat['base_stat']],
        );

        $requiredStats = ['hp', 'attack', 'defense', 'special-attack', 'special-defense', 'speed'];
        if (! isset($payload['id'], $payload['name'], $payload['height'], $payload['weight']) || $stats->only($requiredStats)->count() !== 6) {
            throw new RuntimeException('A PokeAPI retornou um Pokémon com estrutura inesperada.');
        }

        return [
            'pokeapi_id' => $payload['id'],
            'name' => $payload['name'],
            'height' => $payload['height'],
            'weight' => $payload['weight'],
            'base_experience' => $payload['base_experience'] ?? null,
            'types' => collect($payload['types'] ?? [])->pluck('type.name')->values()->all(),
            'hp' => $stats['hp'],
            'attack' => $stats['attack'],
            'defense' => $stats['defense'],
            'special_attack' => $stats['special-attack'],
            'special_defense' => $stats['special-defense'],
            'speed' => $stats['speed'],
        ];
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()->retry(3, 250)->timeout(20);
    }

    private function url(string $path): string
    {
        $baseUrl = $this->baseUrl ?: (string) config('services.pokeapi.url');

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }
}
