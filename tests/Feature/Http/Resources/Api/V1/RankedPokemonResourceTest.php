<?php

declare(strict_types=1);

use App\Models\Pokemon;

it('projects only the requested field for each ranked pokemon with its native type', function (string $field, int|string|null $expected): void {
    Pokemon::factory()->create([
        'pokeapi_id' => 25,
        'name' => 'pikachu',
        'height' => 4,
        'weight' => 60,
        'base_experience' => 112,
        'hp' => 35,
        'attack' => 55,
        'defense' => 40,
        'special_attack' => 50,
        'special_defense' => 50,
        'speed' => 90,
    ]);

    $response = $this->getJson(route('api.v1.pokemons.ranking', ['field' => $field]));

    $response->assertOk();

    expect($response->json('data.0'))->toBe([$field => $expected]);
})->with([
    'PokeAPI identifier' => ['pokeapi_id', 25],
    'name' => ['name', 'pikachu'],
    'height' => ['height', 4],
    'weight' => ['weight', 60],
    'base experience' => ['base_experience', 112],
    'HP' => ['hp', 35],
    'attack' => ['attack', 55],
    'defense' => ['defense', 40],
    'special attack' => ['special_attack', 50],
    'special defense' => ['special_defense', 50],
    'speed' => ['speed', 90],
]);

it('preserves null base experience when it is only the projected field', function (): void {
    Pokemon::factory()->create([
        'pokeapi_id' => 1,
        'hp' => 100,
        'base_experience' => null,
    ]);

    $this->getJson(route('api.v1.pokemons.ranking', ['field' => 'base_experience']))
        ->assertOk()
        ->assertExactJson([
            'data' => [['base_experience' => null]],
            'links' => [
                'first' => route('api.v1.pokemons.ranking', ['field' => 'base_experience', 'page' => 1]),
                'last' => route('api.v1.pokemons.ranking', ['field' => 'base_experience', 'page' => 1]),
                'prev' => null,
                'next' => null,
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'links' => [
                    ['url' => null, 'label' => __('pagination.previous'), 'page' => null, 'active' => false],
                    [
                        'url' => route('api.v1.pokemons.ranking', ['field' => 'base_experience', 'page' => 1]),
                        'label' => '1',
                        'page' => 1,
                        'active' => true,
                    ],
                    ['url' => null, 'label' => __('pagination.next'), 'page' => null, 'active' => false],
                ],
                'path' => route('api.v1.pokemons.ranking'),
                'per_page' => 10,
                'to' => 1,
                'total' => 1,
                'metric' => 'hp',
                'field' => 'base_experience',
                'order' => 'desc',
            ],
        ]);
});

it('returns coherent pagination metadata for a partial last page', function (): void {
    foreach (range(1, 3) as $pokeapiId) {
        Pokemon::factory()->create(['pokeapi_id' => $pokeapiId, 'hp' => $pokeapiId]);
    }

    $this->getJson(route('api.v1.pokemons.ranking', ['page' => 2, 'per_page' => 2]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.from', 3)
        ->assertJsonPath('meta.to', 3)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2);
});

it('returns an empty successful resource collection when no pokemon exists', function (): void {
    $this->getJson(route('api.v1.pokemons.ranking'))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('links.prev', null)
        ->assertJsonPath('links.next', null)
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.from', null)
        ->assertJsonPath('meta.to', null)
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.last_page', 1);
});

it('returns coherent empty metadata for a page above the last page', function (): void {
    Pokemon::factory()->count(3)->create();

    $this->getJson(route('api.v1.pokemons.ranking', ['page' => 3, 'per_page' => 2]))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('links.next', null)
        ->assertJsonPath('meta.current_page', 3)
        ->assertJsonPath('meta.from', null)
        ->assertJsonPath('meta.to', null)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2);
});
