<?php

namespace App\Services\PokeApi;

use App\Exceptions\PokeApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class PokeApiClient
{
    public function listPokemons(int $limit, int $offset = 0): array
    {
        $response = $this->request()->get('/pokemon', [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        if ($response->failed()) {
            throw new PokeApiException('Falha ao listar Pokémon na PokeAPI.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new PokeApiException('Resposta inválida ao listar Pokémon na PokeAPI.');
        }

        return $payload;
    }

    public function getPokemon(string $nameOrId): array
    {
        $result = $this->getPokemons([$nameOrId])[$nameOrId] ?? null;

        if ($result instanceof Throwable) {
            throw $result;
        }

        if (! is_array($result)) {
            throw new PokeApiException("Falha ao obter o Pokémon [{$nameOrId}] na PokeAPI.");
        }

        return $result;
    }

    /**
     * @param  list<string>  $names
     * @return array<string, array|Throwable>
     */
    public function getPokemons(array $names): array
    {
        if ($names === []) {
            return [];
        }

        $responses = Http::pool(function (Pool $pool) use ($names) {
            foreach ($names as $name) {
                $this->request($pool->as($name))->get("/pokemon/{$name}");
            }
        });

        $payloads = [];

        foreach ($names as $name) {
            $payloads[$name] = $this->decodePokemonResponse($name, $responses[$name] ?? null);
        }

        return $payloads;
    }

    private function decodePokemonResponse(string $name, mixed $response): array|Throwable
    {
        if ($response instanceof Throwable) {
            return $response;
        }

        if (! $response instanceof Response || $response->failed()) {
            return new PokeApiException("Falha ao obter o Pokémon [{$name}] na PokeAPI.");
        }

        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['id'], $payload['name'])) {
            return new PokeApiException("Resposta inválida ao obter o Pokémon [{$name}].");
        }

        return $payload;
    }

    private function request(?PendingRequest $pending = null): PendingRequest
    {
        $request = $pending ?? Http::acceptJson();

        return $request
            ->baseUrl(rtrim((string) config('pokeapi.base_url'), '/'))
            ->acceptJson()
            ->timeout((int) config('pokeapi.timeout'))
            ->retry(3, 250, function ($exception) {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                return $exception instanceof RequestException
                    && $exception->response?->serverError();
            });
    }
}
