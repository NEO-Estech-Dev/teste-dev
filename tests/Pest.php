<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @return array<string, mixed>
 */
function pokemonApiPayload(int $id = 1, string $name = 'bulbasaur'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'height' => 7,
        'weight' => 69,
        'base_experience' => 64,
        'stats' => [
            ['base_stat' => 45, 'stat' => ['name' => 'speed']],
            ['base_stat' => 49, 'stat' => ['name' => 'defense']],
            ['base_stat' => 65, 'stat' => ['name' => 'special-defense']],
            ['base_stat' => 45, 'stat' => ['name' => 'hp']],
            ['base_stat' => 65, 'stat' => ['name' => 'special-attack']],
            ['base_stat' => 49, 'stat' => ['name' => 'attack']],
        ],
    ];
}

function configurePokeApi(): void
{
    config()->set('services.pokeapi.base_url', 'https://pokeapi.test/api/v2');
    config()->set('services.pokeapi.timeout', 10);
    config()->set('services.pokeapi.concurrency', 2);
}
