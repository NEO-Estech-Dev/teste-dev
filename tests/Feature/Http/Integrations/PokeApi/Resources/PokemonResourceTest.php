<?php

declare(strict_types=1);

use App\Http\Integrations\PokeApi\DataObjects\PokemonDetails;
use App\Http\Integrations\PokeApi\DataObjects\PokemonPage;
use App\Http\Integrations\PokeApi\DataObjects\PokemonReference;
use App\Http\Integrations\PokeApi\Exceptions\InvalidPokeApiPayload;
use App\Http\Integrations\PokeApi\PokeApiConnector;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    configurePokeApi();
});

it('returns a typed page from the listing endpoint', function () {
    Http::fake([
        'https://pokeapi.test/api/v2/pokemon*' => Http::response([
            'count' => 1302,
            'next' => 'https://pokeapi.test/api/v2/pokemon?limit=1&offset=11',
            'results' => [
                ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
            ],
        ]),
    ]);

    $page = app(PokeApiConnector::class)->pokemons()->list(1, 10);

    expect($page)->toBeInstanceOf(PokemonPage::class)
        ->and($page->items->sole())->toBeInstanceOf(PokemonReference::class)
        ->and($page->items->sole()->name)->toBe('bulbasaur')
        ->and($page->hasNextPage)->toBeTrue()
        ->and($page->total)->toBe(1302);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://pokeapi.test/api/v2/pokemon?limit=1&offset=10');
});

it('returns typed details in reference order', function () {
    Http::fake([
        'https://pokeapi.test/api/v2/pokemon/1/' => Http::response(pokemonApiPayload()),
        'https://pokeapi.test/api/v2/pokemon/2/' => Http::response(pokemonApiPayload(2, 'ivysaur')),
    ]);

    $details = app(PokeApiConnector::class)->pokemons()->details(collect([
        new PokemonReference('ivysaur', 'https://pokeapi.test/api/v2/pokemon/2/'),
        new PokemonReference('bulbasaur', 'https://pokeapi.test/api/v2/pokemon/1/'),
    ]));

    expect($details)->toHaveCount(2)
        ->and($details[0])->toBeInstanceOf(PokemonDetails::class)
        ->and($details[0]->pokeapiId)->toBe(2)
        ->and($details[1]->pokeapiId)->toBe(1);
});

it('forwards detail URLs without validating or rewriting them', function () {
    $url = 'https://details.example.test/custom/pokemon/1/?source=pokeapi';

    Http::fake([
        $url => Http::response(pokemonApiPayload()),
    ]);

    $details = app(PokeApiConnector::class)->pokemons()->details(collect([
        new PokemonReference('bulbasaur', $url),
    ]));

    expect($details->sole()->pokeapiId)->toBe(1);
    Http::assertSent(fn (Request $request): bool => $request->url() === $url);
});

it('rejects an invalid page payload', function () {
    Http::fake([
        'https://pokeapi.test/api/v2/pokemon*' => Http::response([
            'next' => null,
            'results' => [],
        ]),
    ]);

    expect(fn () => app(PokeApiConnector::class)->pokemons()->list(1))
        ->toThrow(InvalidPokeApiPayload::class, 'count');
});
