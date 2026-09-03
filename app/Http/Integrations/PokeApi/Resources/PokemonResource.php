<?php

declare(strict_types=1);

namespace App\Http\Integrations\PokeApi\Resources;

use App\Http\Integrations\PokeApi\DataFactories\PokemonDetailsFactory;
use App\Http\Integrations\PokeApi\DataFactories\PokemonPageFactory;
use App\Http\Integrations\PokeApi\DataObjects\PokemonDetails;
use App\Http\Integrations\PokeApi\DataObjects\PokemonPage;
use App\Http\Integrations\PokeApi\DataObjects\PokemonReference;
use App\Http\Integrations\PokeApi\Exceptions\InvalidPokeApiPayload;
use App\Http\Integrations\PokeApi\PokeApiConnector;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;

final readonly class PokemonResource
{
    public function __construct(private PokeApiConnector $connector) {}

    public function list(int $limit, int $offset = 0): PokemonPage
    {
        $response = $this->connector->send('GET', 'pokemon', [
            'query' => [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);

        return app(PokemonPageFactory::class)->make($this->payload($response));
    }

    /**
     * @param  Collection<int, PokemonReference>  $pokemons
     * @return Collection<int, PokemonDetails>
     */
    public function details(Collection $pokemons): Collection
    {
        $requests = [];

        foreach ($pokemons as $index => $pokemon) {
            $requests[$index] = $pokemon->url;
        }

        if ($requests === []) {
            return app(PokemonDetailsFactory::class)->collection([]);
        }

        $responses = $this->connector->pool($requests);
        $payloads = [];

        foreach ($requests as $index => $url) {
            $response = $responses[$index] ?? null;

            if ($response instanceof Throwable) {
                throw $response;
            }

            if (! $response instanceof Response) {
                throw new InvalidArgumentException(sprintf('PokeAPI did not return a response for %s.', $url));
            }

            $payloads[] = $this->payload($response->throw());
        }

        return app(PokemonDetailsFactory::class)->collection($payloads);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw InvalidPokeApiPayload::missingOrInvalid('response');
        }

        $normalized = [];

        foreach ($payload as $key => $value) {
            if (! is_string($key)) {
                throw InvalidPokeApiPayload::missingOrInvalid('response');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
