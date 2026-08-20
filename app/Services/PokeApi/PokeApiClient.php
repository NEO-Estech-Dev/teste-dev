<?php

namespace App\Services\PokeApi;

use App\Exceptions\PokeApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

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
        $response = $this->request()->get("/pokemon/{$nameOrId}");

        if ($response->failed()) {
            throw new PokeApiException("Falha ao obter o Pokémon [{$nameOrId}] na PokeAPI.");
        }

        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['id'], $payload['name'])) {
            throw new PokeApiException("Resposta inválida ao obter o Pokémon [{$nameOrId}].");
        }

        return $payload;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('pokeapi.base_url'), '/'))
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
