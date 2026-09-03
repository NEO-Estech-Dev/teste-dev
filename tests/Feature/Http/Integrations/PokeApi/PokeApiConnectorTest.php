<?php

declare(strict_types=1);

use App\Http\Integrations\PokeApi\PokeApiConnector;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    configurePokeApi();
});

it('resolves configured connectors from the container', function () {
    $connector = app(PokeApiConnector::class);

    expect($connector)
        ->toBeInstanceOf(PokeApiConnector::class)
        ->not->toBe(app(PokeApiConnector::class));
});

it('sends individual requests through the configured base URL', function () {
    Http::fake([
        'https://pokeapi.test/api/v2/pokemon*' => Http::response(['results' => []]),
    ]);

    $response = app(PokeApiConnector::class)->send('GET', 'pokemon', [
        'query' => ['limit' => 10, 'offset' => 20],
    ]);

    expect($response->successful())->toBeTrue();
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://pokeapi.test/api/v2/pokemon?limit=10&offset=20');
});

it('throws the native HTTP exception without retrying', function () {
    Http::fake([
        'https://pokeapi.test/api/v2/pokemon*' => Http::sequence()
            ->push([], 500)
            ->push(['results' => []]),
    ]);

    expect(fn () => app(PokeApiConnector::class)->send('GET', 'pokemon'))
        ->toThrow(Illuminate\Http\Client\RequestException::class);

    Http::assertSentCount(1);
});

it('pools named requests and preserves their keys', function () {
    Http::fake([
        'https://pokeapi.test/api/v2/pokemon/1/' => Http::response(['id' => 1]),
        'https://pokeapi.test/api/v2/pokemon/2/' => Http::response(['id' => 2]),
    ]);

    $responses = app(PokeApiConnector::class)->pool([
        'second' => 'https://pokeapi.test/api/v2/pokemon/2/',
        'first' => 'https://pokeapi.test/api/v2/pokemon/1/',
    ]);

    expect(array_keys($responses))->toBe(['second', 'first'])
        ->and($responses['second']->json('id'))->toBe(2)
        ->and($responses['first']->json('id'))->toBe(1);
});
