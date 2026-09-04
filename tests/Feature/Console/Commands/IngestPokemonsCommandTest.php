<?php

declare(strict_types=1);

use App\Models\Pokemon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    configurePokeApi();
});

it('paginates up to the requested limit and remains idempotent', function () {
    Http::fake([
        'https://pokeapi.test/api/v2/pokemon?limit=2&offset=0' => Http::response([
            'count' => 3,
            'results' => [
                ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
                ['name' => 'ivysaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/2/'],
            ],
            'next' => 'https://pokeapi.test/api/v2/pokemon?limit=2&offset=2',
        ]),
        'https://pokeapi.test/api/v2/pokemon?limit=1&offset=2' => Http::response([
            'count' => 3,
            'results' => [
                ['name' => 'venusaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/3/'],
            ],
            'next' => 'https://pokeapi.test/api/v2/pokemon?limit=1&offset=3',
        ]),
        'https://pokeapi.test/api/v2/pokemon/1/' => Http::response(pokemonApiPayload()),
        'https://pokeapi.test/api/v2/pokemon/2/' => Http::response(pokemonApiPayload(2, 'ivysaur')),
        'https://pokeapi.test/api/v2/pokemon/3/' => Http::response(pokemonApiPayload(3, 'venusaur')),
    ]);

    $parameters = ['--limit' => '3', '--chunk' => '2'];

    $this->artisan('pokemon:ingest', $parameters)
        ->expectsOutputToContain('synchronized: 3')
        ->assertExitCode(Command::SUCCESS);

    $this->artisan('pokemon:ingest', $parameters)
        ->assertExitCode(Command::SUCCESS);

    expect(Pokemon::query()->count())->toBe(3);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://pokeapi.test/api/v2/pokemon?limit=1&offset=2');
});

it('imports every available page when limit is omitted', function () {
    Http::fake([
        'https://pokeapi.test/api/v2/pokemon?limit=1&offset=0' => Http::response([
            'count' => 2,
            'results' => [
                ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
            ],
            'next' => 'https://pokeapi.test/api/v2/pokemon?limit=1&offset=1',
        ]),
        'https://pokeapi.test/api/v2/pokemon?limit=1&offset=1' => Http::response([
            'count' => 2,
            'results' => [
                ['name' => 'ivysaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/2/'],
            ],
            'next' => null,
        ]),
        'https://pokeapi.test/api/v2/pokemon/1/' => Http::response(pokemonApiPayload()),
        'https://pokeapi.test/api/v2/pokemon/2/' => Http::response(pokemonApiPayload(2, 'ivysaur')),
    ]);

    $this->artisan('pokemon:ingest', ['--chunk' => '1'])
        ->expectsOutputToContain('Requested: 2')
        ->assertExitCode(Command::SUCCESS);

    expect(Pokemon::query()->count())->toBe(2);
});

it('stops before any external request when options are invalid', function (array $parameters) {
    Http::fake();

    $this->artisan('pokemon:ingest', $parameters)
        ->assertExitCode(Command::INVALID);

    Http::assertNothingSent();
})->with([
    'zero limit' => [['--limit' => '0']],
    'empty limit' => [['--limit' => null]],
    'non-integer limit' => [['--limit' => '1.5']],
    'negative offset' => [['--offset' => '-1']],
    'zero chunk' => [['--chunk' => '0']],
    'chunk above maximum' => [['--chunk' => '201']],
]);

it('prevents partial batch persistence when a detail request fails', function () {
    Http::fake([
        'https://pokeapi.test/api/v2/pokemon?limit=2&offset=0' => Http::response([
            'count' => 2,
            'results' => [
                ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
                ['name' => 'ivysaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/2/'],
            ],
            'next' => null,
        ]),
        'https://pokeapi.test/api/v2/pokemon/1/' => Http::response(pokemonApiPayload()),
        'https://pokeapi.test/api/v2/pokemon/2/' => Http::response([], 500),
    ]);

    $this->artisan('pokemon:ingest', ['--limit' => '2', '--chunk' => '2'])
        ->expectsOutputToContain('offset 0')
        ->assertExitCode(Command::FAILURE);

    expect(Pokemon::query()->count())->toBe(0);
});

it('prevents partial batch persistence when payload conversion fails', function () {
    $unconvertiblePayload = pokemonApiPayload(2, 'ivysaur');
    $unconvertiblePayload['name'] = [];

    Http::fake([
        'https://pokeapi.test/api/v2/pokemon?limit=2&offset=0' => Http::response([
            'count' => 2,
            'results' => [
                ['name' => 'bulbasaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/1/'],
                ['name' => 'ivysaur', 'url' => 'https://pokeapi.test/api/v2/pokemon/2/'],
            ],
            'next' => null,
        ]),
        'https://pokeapi.test/api/v2/pokemon/1/' => Http::response(pokemonApiPayload()),
        'https://pokeapi.test/api/v2/pokemon/2/' => Http::response($unconvertiblePayload),
    ]);

    $this->artisan('pokemon:ingest', ['--limit' => '2', '--chunk' => '2'])
        ->assertExitCode(Command::FAILURE);

    expect(Pokemon::query()->count())->toBe(0);
});
