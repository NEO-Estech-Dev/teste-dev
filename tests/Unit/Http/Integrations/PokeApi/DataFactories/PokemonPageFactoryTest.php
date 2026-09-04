<?php

declare(strict_types=1);

use App\Http\Integrations\PokeApi\DataFactories\PokemonPageFactory;
use App\Http\Integrations\PokeApi\DataObjects\PokemonPage;
use App\Http\Integrations\PokeApi\DataObjects\PokemonReference;
use App\Http\Integrations\PokeApi\Exceptions\InvalidPokeApiPayload;

it('maps the page envelope and its references', function () {
    $page = app(PokemonPageFactory::class)->make([
        'count' => 1302,
        'next' => null,
        'results' => [
            ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
        ],
    ]);

    expect($page)
        ->toBeInstanceOf(PokemonPage::class)
        ->hasNextPage->toBeFalse()
        ->total->toBe(1302)
        ->and($page->items->sole())->toBeInstanceOf(PokemonReference::class)
        ->and($page->toArray())->toBe([
            'items' => [
                ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
            ],
            'hasNextPage' => false,
            'total' => 1302,
        ]);
});

it('rejects invalid page envelope fields', function (array $payload, string $field) {
    expect(fn (): PokemonPage => app(PokemonPageFactory::class)->make($payload))
        ->toThrow(InvalidPokeApiPayload::class, $field);
})->with([
    'missing results' => [['count' => 1, 'next' => null], 'results'],
    'missing next' => [['count' => 1, 'results' => []], 'next'],
    'invalid total' => [['count' => -1, 'next' => null, 'results' => []], 'count'],
]);
