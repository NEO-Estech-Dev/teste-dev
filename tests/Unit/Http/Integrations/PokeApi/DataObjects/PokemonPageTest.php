<?php

declare(strict_types=1);

use App\Http\Integrations\PokeApi\DataObjects\PokemonPage;
use App\Http\Integrations\PokeApi\DataObjects\PokemonReference;
use Illuminate\Support\Collection;

it('exposes its values and serializes references as a list', function () {
    $items = new Collection([
        3 => new PokemonReference('bulbasaur', 'https://pokeapi.test/api/v2/pokemon/1/'),
        8 => new PokemonReference('ivysaur', 'https://pokeapi.test/api/v2/pokemon/2/'),
    ]);
    $page = new PokemonPage(items: $items, hasNextPage: true, total: 1302);

    expect($page)
        ->items->toBe($items)
        ->hasNextPage->toBeTrue()
        ->total->toBe(1302)
        ->and($page->toArray())->toBe([
            'items' => [
                ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
                ['name' => 'ivysaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/2/'],
            ],
            'hasNextPage' => true,
            'total' => 1302,
        ]);
});

it('is immutable', function () {
    $page = new PokemonPage(items: collect(), hasNextPage: false, total: 0);

    expect(function () use ($page): void {
        $page->total = 1;
    })->toThrow(Error::class);
});
