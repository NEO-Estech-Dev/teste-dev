<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('stats')]
#[Fillable('id','name', 'base_stat', 'effort', 'pokemon_id')]
#[Hidden('created_at','updated_at')]
class Stat extends Model
{

}
