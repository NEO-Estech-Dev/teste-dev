<?php

declare(strict_types=1);

use App\Models\Pokemon;

it('serializes every persisted column to an array', function (): void {
    $pokemon = Pokemon::factory()->create()->refresh();

    expect(array_keys($pokemon->toArray()))->toEqual([
        'id',
        'pokeapi_id',
        'name',
        'height',
        'weight',
        'base_experience',
        'hp',
        'attack',
        'defense',
        'special_attack',
        'special_defense',
        'speed',
        'created_at',
        'updated_at',
    ]);
});
