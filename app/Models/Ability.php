<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ability extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'is_hidden'
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
    ];

    public function pokemon()
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_abilities', 'ability_id', 'pokemon_id')
                    ->withPivot(['is_hidden', 'slot'])
                    ->withTimestamps();
    }
}
