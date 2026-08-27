<?php

namespace App\Services\Pokemon;

use App\Repositories\Pokemon\PokemonRepository;

class IndexPokemonService {

    private PokemonRepository $pokemonRepository;

    public function __construct(PokemonRepository $pokemonRepository)
    {
        $this->pokemonRepository = $pokemonRepository;
    }

    private const ALLOWED_FIELDS = [
        'id',
        'pokemon_id',
        'name',
        'height',
        'weight',
        'order',
        'specie',
        'base_experience'
    ];

    public function execute(string $page, string $limit, ?string $metric, ?string $fields, string $order) 
    {
        $fields = $fields
            ? explode(',', $fields)
            : self::ALLOWED_FIELDS;

        $query = $this->pokemonRepository
            ->newQuery()
            ->select($fields)
            ->with(['game_indices', 'stats']);

        if ($metric !== null) {
            $query->orderBy($metric, $order);
        }

        return $query->paginate((int) $limit, page: (int) $page);
    }
}