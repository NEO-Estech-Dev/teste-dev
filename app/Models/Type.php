<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url'
    ];

    public function pokemon()
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_types', 'type_id', 'pokemon_id')
                    ->withPivot('slot')
                    ->withTimestamps();
    }
}
