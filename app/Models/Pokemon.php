<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pokemon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pokemon_id',
        'height',
        'weight',
        'base_experience',
        'order',
        'is_default',
        'sprite_url',
        'hp',
        'attack',
        'defense',
        'special_attack',
        'special_defense',
        'speed',
        'total_stats'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function types()
    {
        return $this->belongsToMany(Type::class, 'pokemon_types', 'pokemon_id', 'type_id')
                    ->withPivot('slot')
                    ->withTimestamps();
    }

    public function abilities()
    {
        return $this->belongsToMany(Ability::class, 'pokemon_abilities', 'pokemon_id', 'ability_id')
                    ->withPivot(['is_hidden', 'slot'])
                    ->withTimestamps();
    }
}
