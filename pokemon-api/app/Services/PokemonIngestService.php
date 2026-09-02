<?php

namespace App\Services;

use App\Models\Pokemon;
use App\Models\PokemonStat;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PokemonIngestService
{
    public function ingest(int $limit = 151): void
    {
        $response = Http::get("https://pokeapi.co/api/v2/pokemon?limit={$limit}");

        if ($response->failed()) {
            throw new RuntimeException('Falha ao consultar a PokeAPI.');
        }

        foreach ($response->json('results', []) as $item) {
            $detail = Http::get($item['url'])->json();

            $pokemon = Pokemon::updateOrCreate(
                ['pokeapi_id' => $detail['id']],
                [
                    'name' => $detail['name'],
                    'height' => $detail['height'],
                    'weight' => $detail['weight'],
                    'base_experience' => $detail['base_experience'],
                    'sprite_url' => $detail['sprites']['front_default'] ?? null,
                ]
            );

            foreach ($detail['stats'] as $stat) {
                PokemonStat::updateOrCreate(
                    [
                        'pokemon_id' => $pokemon->id,
                        'stat_name' => $stat['stat']['name'],
                    ],
                    [
                        'base_value' => $stat['base_stat'],
                    ]
                );
            }
        }
    }
}
