<?php

declare(strict_types=1);

use App\Models\Pokemon;
use App\Queries\RankPokemonsByMetricQuery;
use Illuminate\Pagination\LengthAwarePaginator;

it('returns a length aware paginator with only the requested column selected', function (): void {
    Pokemon::factory()->create([
        'pokeapi_id' => 1,
        'name' => 'bulbasaur',
        'hp' => 45,
    ]);

    $paginator = app(RankPokemonsByMetricQuery::class)->handle(
        metric: 'hp',
        field: 'name',
        order: 'desc',
        page: 1,
        perPage: 10,
    );

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($paginator->total())->toBe(1)
        ->and($paginator->items()[0])->toBeInstanceOf(Pokemon::class)
        ->and($paginator->items()[0]->getAttributes())->toBe(['name' => 'bulbasaur']);
});

it('uses pokeapi id as the deterministic tiebreaker in both directions', function (string $order, array $expectedIds): void {
    foreach ([3, 1, 2] as $pokeapiId) {
        Pokemon::factory()->create([
            'pokeapi_id' => $pokeapiId,
            'hp' => 50,
        ]);
    }

    $paginator = app(RankPokemonsByMetricQuery::class)->handle(
        metric: 'hp',
        field: 'pokeapi_id',
        order: $order,
        page: 1,
        perPage: 10,
    );

    expect(collect($paginator->items())->pluck('pokeapi_id')->all())->toBe($expectedIds);
})->with([
    'descending' => ['desc', [3, 2, 1]],
    'ascending' => ['asc', [1, 2, 3]],
]);

it('excludes null base experience from that ranking and its total', function (): void {
    Pokemon::factory()->create(['pokeapi_id' => 1, 'base_experience' => null]);
    Pokemon::factory()->create(['pokeapi_id' => 2, 'base_experience' => 10]);
    Pokemon::factory()->create(['pokeapi_id' => 3, 'base_experience' => 20]);

    $paginator = app(RankPokemonsByMetricQuery::class)->handle(
        metric: 'base_experience',
        field: 'pokeapi_id',
        order: 'desc',
        page: 1,
        perPage: 10,
    );

    expect($paginator->total())->toBe(2)
        ->and(collect($paginator->items())->pluck('pokeapi_id')->all())->toBe([3, 2]);
});

it('applies page size and offset before materializing results', function (): void {
    foreach (range(1, 5) as $pokeapiId) {
        Pokemon::factory()->create([
            'pokeapi_id' => $pokeapiId,
            'hp' => $pokeapiId,
        ]);
    }

    $paginator = app(RankPokemonsByMetricQuery::class)->handle(
        metric: 'hp',
        field: 'pokeapi_id',
        order: 'desc',
        page: 2,
        perPage: 2,
    );

    expect($paginator->currentPage())->toBe(2)
        ->and($paginator->perPage())->toBe(2)
        ->and($paginator->total())->toBe(5)
        ->and(collect($paginator->items())->pluck('pokeapi_id')->all())->toBe([3, 2]);
});

it('rejects unsafe values when called outside the http layer', function (
    string $metric,
    string $field,
    string $order,
    int $page,
    int $perPage,
): void {
    expect(fn () => app(RankPokemonsByMetricQuery::class)->handle(
        metric: $metric,
        field: $field,
        order: $order,
        page: $page,
        perPage: $perPage,
    ))->toThrow(InvalidArgumentException::class);
})->with([
    'metric' => ['unknown', 'name', 'desc', 1, 10],
    'field' => ['hp', 'password', 'desc', 1, 10],
    'order' => ['hp', 'name', 'random', 1, 10],
    'page' => ['hp', 'name', 'desc', 0, 10],
    'minimum page size' => ['hp', 'name', 'desc', 1, 0],
    'maximum page size' => ['hp', 'name', 'desc', 1, 101],
]);
