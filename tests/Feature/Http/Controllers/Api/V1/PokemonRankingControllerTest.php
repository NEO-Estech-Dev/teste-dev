<?php

declare(strict_types=1);

use App\Enums\PokemonMetric;
use App\Models\Pokemon;
use Illuminate\Support\Facades\Route;

it('uses the documented ranking defaults', function (): void {
    foreach (range(1, 12) as $pokeapiId) {
        Pokemon::factory()->create([
            'pokeapi_id' => $pokeapiId,
            'name' => "pokemon-{$pokeapiId}",
            'hp' => $pokeapiId,
        ]);
    }

    $response = $this->getJson(route('api.v1.pokemons.ranking'));

    $response
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('data.0.name', 'pokemon-12')
        ->assertJsonPath('data.9.name', 'pokemon-3')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.metric', 'hp')
        ->assertJsonPath('meta.field', 'name')
        ->assertJsonPath('meta.order', 'desc');
});

it('ranks by the requested metric', function (): void {
    Pokemon::factory()->create([
        'pokeapi_id' => 1,
        'name' => 'high-hp',
        'hp' => 100,
        'attack' => 10,
    ]);
    Pokemon::factory()->create([
        'pokeapi_id' => 2,
        'name' => 'high-attack',
        'hp' => 10,
        'attack' => 100,
    ]);

    $response = $this->getJson(route('api.v1.pokemons.ranking', ['metric' => 'attack']));

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'high-attack')
        ->assertJsonPath('meta.metric', 'attack');
});

it('supports both ranking directions', function (string $order, array $expectedNames): void {
    Pokemon::factory()->create(['pokeapi_id' => 1, 'name' => 'slow', 'speed' => 10]);
    Pokemon::factory()->create(['pokeapi_id' => 2, 'name' => 'average', 'speed' => 50]);
    Pokemon::factory()->create(['pokeapi_id' => 3, 'name' => 'fast', 'speed' => 100]);

    $response = $this->getJson(route('api.v1.pokemons.ranking', [
        'metric' => 'speed',
        'order' => $order,
    ]));

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())->toBe($expectedNames);
})->with([
    'descending' => ['desc', ['fast', 'average', 'slow']],
    'ascending' => ['asc', ['slow', 'average', 'fast']],
]);

it('paginates tied values deterministically without gaps or duplicates', function (): void {
    foreach (range(1, 5) as $pokeapiId) {
        Pokemon::factory()->create([
            'pokeapi_id' => $pokeapiId,
            'name' => "pokemon-{$pokeapiId}",
            'hp' => 50,
        ]);
    }

    $firstPage = $this->getJson(route('api.v1.pokemons.ranking', ['page' => 1, 'per_page' => 2]));
    $secondPage = $this->getJson(route('api.v1.pokemons.ranking', ['page' => 2, 'per_page' => 2]));
    $thirdPage = $this->getJson(route('api.v1.pokemons.ranking', ['page' => 3, 'per_page' => 2]));

    $names = collect([$firstPage, $secondPage, $thirdPage])
        ->flatMap(static fn ($response): array => $response->json('data'))
        ->pluck('name')
        ->all();

    expect($names)->toBe([
        'pokemon-5',
        'pokemon-4',
        'pokemon-3',
        'pokemon-2',
        'pokemon-1',
    ])->and(array_unique($names))->toHaveCount(5);
});

it('accepts the minimum and maximum page sizes', function (int $perPage, int $expectedCount): void {
    Pokemon::factory()->count(3)->create();

    $response = $this->getJson(route('api.v1.pokemons.ranking', ['per_page' => $perPage]));

    $response
        ->assertOk()
        ->assertJsonCount($expectedCount, 'data')
        ->assertJsonPath('meta.per_page', $perPage);
})->with([
    'minimum' => [1, 1],
    'maximum' => [100, 3],
]);

it('preserves only supplied ranking parameters in pagination links', function (): void {
    Pokemon::factory()->count(3)->create();

    $response = $this->getJson(route('api.v1.pokemons.ranking', [
        'metric' => 'speed',
        'field' => 'pokeapi_id',
        'order' => 'asc',
        'per_page' => 1,
        'ignored' => 'value',
    ]));

    $response->assertOk();

    foreach (['links.first', 'links.last', 'links.next'] as $path) {
        $query = [];
        parse_str((string) parse_url((string) $response->json($path), PHP_URL_QUERY), $query);

        expect($query)->toMatchArray([
            'metric' => 'speed',
            'field' => 'pokeapi_id',
            'order' => 'asc',
            'per_page' => '1',
        ])->not->toHaveKey('ignored');
    }
});

it('does not append omitted default parameters to pagination links', function (): void {
    Pokemon::factory()->create();

    $response = $this->getJson(route('api.v1.pokemons.ranking'));

    $response->assertOk();

    $query = [];
    parse_str((string) parse_url((string) $response->json('links.first'), PHP_URL_QUERY), $query);

    expect($query)->toBe(['page' => '1']);
});

it('accepts every documented metric', function (string $metric): void {
    Pokemon::factory()->create(['base_experience' => 10]);

    $this->getJson(route('api.v1.pokemons.ranking', ['metric' => $metric]))
        ->assertOk()
        ->assertJsonPath('meta.metric', $metric);
})->with(PokemonMetric::values());

it('rejects invalid ranking parameters', function (array $parameters, string $invalidParameter): void {
    $this->getJson(route('api.v1.pokemons.ranking', $parameters))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($invalidParameter);
})->with([
    'metric' => [['metric' => 'unknown'], 'metric'],
    'field' => [['field' => 'password'], 'field'],
    'order' => [['order' => 'random'], 'order'],
    'page below minimum' => [['page' => 0], 'page'],
    'non-integer page' => [['page' => 'invalid'], 'page'],
    'page size below minimum' => [['per_page' => 0], 'per_page'],
    'page size above maximum' => [['per_page' => 101], 'per_page'],
]);

it('exposes the ranking route only through get', function (): void {
    $route = Route::getRoutes()->getByName('api.v1.pokemons.ranking');

    expect($route)->not->toBeNull()
        ->and($route?->uri())->toBe('api/v1/pokemons/ranking')
        ->and($route?->methods())->toBe(['GET', 'HEAD']);

    $this->postJson('/api/v1/pokemons/ranking')->assertMethodNotAllowed();
});
