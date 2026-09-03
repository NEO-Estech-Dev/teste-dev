<?php

declare(strict_types=1);

use App\Http\Integrations\PokeApi\DataObjects\PokemonDetails;

it('exposes its values and array representation', function () {
    $details = new PokemonDetails(
        pokeapiId: 1,
        name: 'bulbasaur',
        height: 7,
        weight: 69,
        baseExperience: 64,
        hp: 45,
        attack: 49,
        defense: 49,
        specialAttack: 65,
        specialDefense: 65,
        speed: 45,
    );

    expect($details)
        ->pokeapiId->toBe(1)
        ->name->toBe('bulbasaur')
        ->height->toBe(7)
        ->weight->toBe(69)
        ->baseExperience->toBe(64)
        ->hp->toBe(45)
        ->attack->toBe(49)
        ->defense->toBe(49)
        ->specialAttack->toBe(65)
        ->specialDefense->toBe(65)
        ->speed->toBe(45)
        ->and($details->toArray())->toBe([
            'pokeapiId' => 1,
            'name' => 'bulbasaur',
            'height' => 7,
            'weight' => 69,
            'baseExperience' => 64,
            'hp' => 45,
            'attack' => 49,
            'defense' => 49,
            'specialAttack' => 65,
            'specialDefense' => 65,
            'speed' => 45,
        ]);
});

it('accepts a null base experience', function () {
    $details = new PokemonDetails(1, 'bulbasaur', 7, 69, null, 45, 49, 49, 65, 65, 45);

    expect($details->toArray()['baseExperience'])->toBeNull();
});

it('is immutable', function () {
    $details = new PokemonDetails(1, 'bulbasaur', 7, 69, 64, 45, 49, 49, 65, 65, 45);

    expect(function () use ($details): void {
        $details->name = 'ivysaur';
    })->toThrow(Error::class);
});
