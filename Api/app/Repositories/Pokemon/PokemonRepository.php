<?php

namespace App\Repositories\Pokemon;

use App\Models\Pokemon;
use App\Repositories\Base\BaseRepository;

class PokemonRepository extends BaseRepository {

    protected $modelClass = Pokemon::class;
    
}