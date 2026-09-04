<?php

declare(strict_types=1);

use App\Http\Integrations\PokeApi\DataObjects\PokemonReference;

it('exposes its values and array representation', function () {
    $reference = new PokemonReference(
        name: 'bulbasaur',
        url: 'https://pokeapi.test/api/v2/pokemon/1/',
    );

    expect($reference)
        ->name->toBe('bulbasaur')
        ->url->toBe('https://pokeapi.test/api/v2/pokemon/1/')
        ->and($reference->toArray())->toBe([
            'name' => 'bulbasaur',
            'url' => 'https://pokeapi.test/api/v2/pokemon/1/',
        ]);
});

it('is immutable', function () {
    $reference = new PokemonReference('bulbasaur', 'https://pokeapi.test/api/v2/pokemon/1/');

    expect(function () use ($reference): void {
        $reference->name = 'ivysaur';
    })->toThrow(Error::class);
});
