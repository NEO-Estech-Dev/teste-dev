<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pokemon extends Model
{
    use HasFactory;

    protected $table = 'pokemon';

    protected $fillable = [
        'pokeapi_id',
        'name',
        'hp',
        'attack',
        'defense',
        'special_attack',
        'special_defense',
        'speed',
        'weight',
        'height',
    ];
}
