<?php

declare(strict_types=1);

use App\Http\Integrations\PokeApi\DataFactories\PokemonReferenceFactory;
use App\Http\Integrations\PokeApi\DataObjects\PokemonReference;
use App\Http\Integrations\PokeApi\Exceptions\InvalidPokeApiPayload;

it('maps a reference and exposes its array representation', function () {
    $reference = app(PokemonReferenceFactory::class)->make([
        'name' => 'bulbasaur',
        'url' => 'https://pokeapi.test/api/v2/pokemon/1/',
    ]);

    expect($reference)
        ->toBeInstanceOf(PokemonReference::class)
        ->and($reference->toArray())->toBe([
            'name' => 'bulbasaur',
            'url' => 'https://pokeapi.test/api/v2/pokemon/1/',
        ]);
});

it('maps a collection of references in order', function () {
    $references = app(PokemonReferenceFactory::class)->collection([
        3 => ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
        8 => ['name' => 'ivysaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/2/'],
    ]);

    expect($references->map->toArray()->all())->toBe([
        ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
        ['name' => 'ivysaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/2/'],
    ]);
});

it('rejects invalid reference fields', function (array $payload, string $field) {
    expect(fn (): PokemonReference => app(PokemonReferenceFactory::class)->make($payload))
        ->toThrow(InvalidPokeApiPayload::class, $field);
})->with([
    'missing name' => [['url' => 'https://pokeapi.test/api/v2/pokemon/1/'], 'results.name'],
    'non-string name' => [['name' => 1, 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'], 'results.name'],
    'empty name' => [['name' => '', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'], 'results.name'],
    'missing URL' => [['name' => 'bulbasaur'], 'results.url'],
    'non-string URL' => [['name' => 'bulbasaur', 'url' => 1], 'results.url'],
    'empty URL' => [['name' => 'bulbasaur', 'url' => ''], 'results.url'],
]);

it('rejects invalid collection items', function (array $items, string $field) {
    expect(fn () => app(PokemonReferenceFactory::class)->collection($items))
        ->toThrow(InvalidPokeApiPayload::class, $field);
})->with([
    'non-array item' => [[3 => ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'], 8 => 'ivysaur'], 'results.1'],
    'non-string item key' => [[[0 => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/']], 'results.0'],
]);
