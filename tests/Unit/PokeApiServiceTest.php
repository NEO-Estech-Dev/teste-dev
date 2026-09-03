<?php

namespace Tests\Unit;

use App\Services\PokeApiService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PokeApiServiceTest extends TestCase
{
    public function test_it_transforms_only_relevant_pokeapi_data(): void
    {
        $payload = [
            'id' => 25,
            'name' => 'pikachu',
            'height' => 4,
            'weight' => 60,
            'base_experience' => 112,
            'types' => [['slot' => 1, 'type' => ['name' => 'electric']]],
            'stats' => [
                ['base_stat' => 35, 'stat' => ['name' => 'hp']],
                ['base_stat' => 55, 'stat' => ['name' => 'attack']],
                ['base_stat' => 40, 'stat' => ['name' => 'defense']],
                ['base_stat' => 50, 'stat' => ['name' => 'special-attack']],
                ['base_stat' => 50, 'stat' => ['name' => 'special-defense']],
                ['base_stat' => 90, 'stat' => ['name' => 'speed']],
            ],
            'moves' => [['ignored' => true]],
        ];

        self::assertSame([
            'pokeapi_id' => 25,
            'name' => 'pikachu',
            'height' => 4,
            'weight' => 60,
            'base_experience' => 112,
            'types' => ['electric'],
            'hp' => 35,
            'attack' => 55,
            'defense' => 40,
            'special_attack' => 50,
            'special_defense' => 50,
            'speed' => 90,
        ], (new PokeApiService)->transform($payload));
    }

    public function test_it_rejects_an_unexpected_pokeapi_payload(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('estrutura inesperada');

        (new PokeApiService)->transform([
            'id' => 25,
            'name' => 'pikachu',
            'height' => 4,
            'weight' => 60,
            'stats' => [],
        ]);
    }
}
