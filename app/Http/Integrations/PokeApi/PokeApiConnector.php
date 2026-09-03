<?php

declare(strict_types=1);

namespace App\Http\Integrations\PokeApi;

use App\Http\Integrations\PokeApi\Resources\PokemonResource;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class PokeApiConnector
{
    public function __construct(private PendingRequest $request) {}

    public static function register(Application $app): void
    {
        $app->bind(
            abstract: self::class,
            concrete: fn (): self => new self(
                request: Http::baseUrl(
                    url: Config::string('services.pokeapi.base_url'),
                )->timeout(
                    seconds: Config::integer('services.pokeapi.timeout'),
                )->asJson()->acceptJson(),
            ),
        );
    }

    public function pokemons(): PokemonResource
    {
        return new PokemonResource(connector: $this);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function send(string $method, string $uri, array $options = []): Response
    {
        return $this->request->send(
            method: $method,
            url: $uri,
            options: $options,
        )->throw();
    }

    /**
     * @param  array<array-key, string>  $requests
     * @return array<array-key, Response|Throwable>
     */
    public function pool(array $requests): array
    {
        return Http::pool(
            callback: function (Pool $pool) use ($requests): array {
                $pending = [];

                foreach ($requests as $key => $url) {
                    $pending[] = $pool
                        ->as((string) $key)
                        ->timeout(Config::integer('services.pokeapi.timeout'))
                        ->acceptJson()
                        ->get($url);
                }

                return $pending;
            },
            concurrency: max(0, Config::integer('services.pokeapi.concurrency')),
        );
    }
}
