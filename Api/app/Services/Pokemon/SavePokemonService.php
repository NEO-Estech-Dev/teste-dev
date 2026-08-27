<?php

namespace App\Services\Pokemon;

use App\Repositories\Pokemon\PokemonRepository;

class SavePokemonService {
    
    private PokemonRepository $pokemonRepository;

    public function __construct(PokemonRepository $pokemonRepository)
    {
        $this->pokemonRepository = $pokemonRepository;
    }

    public function execute(array $data): void
    {
        $pokemons = array_map(function ($data) {
            return [ 
                "name"       => $data['name'],
                "height"     => $data['height'], 
                "weight"     => $data['weight'],
                "order"      => $data['order'],
                "specie"     => $data['specie'],
                "base_experience"  => $data['base_experience'],
                "pokemon_id" => $data['pokemon_id'] 
            ];
        }, $data);

        $this->pokemonRepository->newQuery()->upsert(
            $pokemons,
            ['pokemon_id'],
            ['name', 'height', 'weight', 'order', 'specie']
        );
    }
}