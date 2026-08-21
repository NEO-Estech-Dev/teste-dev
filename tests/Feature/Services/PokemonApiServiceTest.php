<?php

namespace Tests\Feature\Services;

use App\Services\PokemonApiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PokemonApiServiceTest extends TestCase
{
    public function test_can_fetch_pokemons_from_api(): void
    {
        Http::fake([
            'https://pokeapi.co/api/v2/pokemon/*' => Http::response([
                'count' => 2,
                'next' => null,
                'previous' => null,
                'results' => [
                    [
                        'name' => 'bulbasaur',
                        'url' => 'https://pokeapi.co/api/v2/pokemon/1/',
                    ],
                    [
                        'name' => 'ivysaur',
                        'url' => 'https://pokeapi.co/api/v2/pokemon/2/',
                    ],
                ],
            ], 200),
        ]);

        $service = app(PokemonApiService::class);

        $result = $service->getPokemons(
            'https://pokeapi.co/api/v2/pokemon/?offset=0&limit=2'
        );

        $this->assertNotNull($result);
        $this->assertCount(2, $result['results']);
        $this->assertEquals('bulbasaur', $result['results'][0]['name']);
    }
}