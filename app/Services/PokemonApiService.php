<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PokemonApiService
{
    public function getPokemons(string $url): ?array
    {
        try {
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                Log::error('Erro ao se conectar à PokeAPI', [
                    'status' => $response->status(),
                    'url' => $url,
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Conexão com a PokeAPI falhou', [
                'message' => $e->getMessage(),
                'url' => $url,
            ]);

            return null;
        }
    }

    public function getBaseUrl(): string
    {
        $baseUrl = env('POKEAPI_BASE_URL');

        if (!$baseUrl) {
            throw new \RuntimeException(
                'POKEAPI_BASE_URL não está configurada.'
            );
        }

        return rtrim($baseUrl, '/');
    }

    public function formatPokemonData(array $pokemon): ?array
    {
        if (!isset($pokemon['name'], $pokemon['url'])) {
            Log::error('Informações relevantes ausentes para o Pokémon', [
                'name' => $pokemon['name'] ?? null,
                'url' => $pokemon['url'] ?? null,
            ]);

            return null;
        }

        $externalId = basename(rtrim($pokemon['url'], '/'));

        if (!ctype_digit($externalId)) {
            Log::error('ID do Pokémon inválido', [
                'name' => $pokemon['name'],
                'url' => $pokemon['url'],
            ]);

            return null;
        }

        return [
            'name' => $pokemon['name'],
            'external_id' => (int) $externalId,
            'external_url' => $pokemon['url'],
        ];
    }
}