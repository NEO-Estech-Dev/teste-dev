<?php

declare(strict_types=1);

namespace App\Services\PokeApi;

use App\DTOs\PokemonData;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Thin client over the public PokeAPI.
 *
 * The detail endpoint has to be called once per Pokemon (~1350 calls), so the
 * requests are issued through an HTTP pool: sequentially this takes minutes,
 * pooled it takes seconds.
 */
final class PokeApiClient
{
    private readonly string $baseUrl;

    private readonly int $timeout;

    private readonly int $retryTimes;

    private readonly int $retrySleep;

    public function __construct(private readonly Factory $http)
    {
        $this->baseUrl = rtrim((string) config('pokeapi.base_url'), '/');
        $this->timeout = (int) config('pokeapi.timeout');
        $this->retryTimes = (int) config('pokeapi.retry.times');
        $this->retrySleep = (int) config('pokeapi.retry.sleep');
    }

    /**
     * Total number of Pokemon exposed by the API.
     */
    public function count(): int
    {
        $response = $this->request()->get($this->baseUrl.'/pokemon', [
            'limit' => 1,
            'offset' => 0,
        ]);

        $response->throw();

        return (int) $response->json('count', 0);
    }

    /**
     * Detail URLs for the requested slice of the Pokedex.
     *
     * @return list<string>
     */
    public function listResourceUrls(?int $limit = null, int $offset = 0): array
    {
        $limit ??= max($this->count() - $offset, 0);

        if ($limit === 0) {
            return [];
        }

        $response = $this->request()->get($this->baseUrl.'/pokemon', [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $response->throw();

        return array_values(array_filter(array_map(
            static fn (array $result): ?string => isset($result['url']) ? (string) $result['url'] : null,
            (array) $response->json('results', []),
        )));
    }

    /**
     * Fetch a batch of detail URLs concurrently.
     *
     * Requests are pooled `concurrency` at a time; a failed URL is reported
     * back instead of aborting the batch, so one bad record cannot sink a run.
     *
     * @param  list<string>  $urls
     * @return array{pokemon: list<PokemonData>, failed: list<string>}
     */
    public function fetchMany(array $urls, int $concurrency): array
    {
        $pokemon = [];
        $failed = [];

        foreach (array_chunk($urls, max($concurrency, 1)) as $batch) {
            $responses = $this->http->pool(fn (Pool $pool): array => array_map(
                fn (string $url) => $pool->as($url)
                    ->timeout($this->timeout)
                    ->retry($this->retryTimes, $this->retrySleep, throw: false)
                    ->acceptJson()
                    ->get($url),
                $batch,
            ));

            foreach ($batch as $url) {
                $response = $responses[$url] ?? null;

                if (! $response instanceof Response || ! $response->successful()) {
                    $failed[] = $url;

                    continue;
                }

                try {
                    $pokemon[] = PokemonData::fromApi((array) $response->json());
                } catch (Throwable) {
                    $failed[] = $url;
                }
            }
        }

        return ['pokemon' => $pokemon, 'failed' => $failed];
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleep, throw: false)
            ->acceptJson();
    }
}
