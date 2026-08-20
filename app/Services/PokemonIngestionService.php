<?php

namespace App\Services;

use App\Jobs\ProcessPokemon;
use App\Models\Pokemon;

class PokemonIngestionService
{
    public Int $offset;
    public Int $limit;

    public function __construct(private PokemonApiService $pokemonApi, $offset = 0, $limit = 20)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function handle(): void
    {
        $url = $this->pokemonApi->getBaseUrl() . "/pokemon/?offset=$this->offset&limit=$this->limit";

        do {
            $data = $this->pokemonApi->getPokemons($url);

            if (!$data) {
                break;
            }

            foreach ($data['results'] ?? [] as $pokemon) {
                $pokemonData = $this->pokemonApi->formatPokemonData($pokemon);

                if (!$pokemonData) {
                    continue;
                }

                $pokemonModel = Pokemon::updateOrCreate(
                    [
                        'external_id' => $pokemonData['external_id'],
                    ],
                    $pokemonData
                );
                
                if (!$pokemonModel->id) {
                    continue;
                }
                
                ProcessPokemon::dispatch($pokemonModel->id);
            }

            $url = $data['next'] ?? null;

        } while ($url);
    }

    public function savePokemon(Array $pokemonData)
    {
        $pokemonExists = Pokemon::first($pokemonData['external_id']);

        if ($pokemonExists) {
            return;
        }

        Pokemon::Create($pokemonData);
    }
}