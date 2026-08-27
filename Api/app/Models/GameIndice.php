<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('games_indices')]
#[Fillable('id', 'game_index', 'version_name', 'pokemon_id')]
#[Hidden('created_at','updated_at')]
class GameIndice extends Model
{

}
