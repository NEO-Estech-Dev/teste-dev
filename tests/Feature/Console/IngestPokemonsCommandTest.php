<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestPokemonsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_deve_ingerir_dados_mockando_a_pokeapi_com_sucesso(): void
    {
        // 1. FAKE HTTP (Mock) - URL Específica primeiro!
        Http::fake([
            // Retorna os detalhes do Pokemon
            'pokeapi.co/api/v2/pokemon/25/' => Http::response([
                'id' => 25,
                'name' => 'pikachu',
                'weight' => 60,
                'height' => 4,
                'stats' => [
                    ['stat' => ['name' => 'hp'], 'base_stat' => 35],
                    ['stat' => ['name' => 'attack'], 'base_stat' => 55],
                    ['stat' => ['name' => 'defense'], 'base_stat' => 40],
                    ['stat' => ['name' => 'special-attack'], 'base_stat' => 50],
                    ['stat' => ['name' => 'special-defense'], 'base_stat' => 50],
                    ['stat' => ['name' => 'speed'], 'base_stat' => 90],
                ]
            ], 200),
            
            // Retorna a lista principal
            'pokeapi.co/api/v2/pokemon*' => Http::response([
                'results' => [
                    ['name' => 'pikachu', 'url' => 'https://pokeapi.co/api/v2/pokemon/25/']
                ]
            ], 200),
        ]);

        // 2. Executa o comando
        $this->artisan('app:ingest-pokemons --limit=1')->assertSuccessful();

        // 3. Verifica se salvou no banco
        $this->assertDatabaseHas('pokemon', [
            'name' => 'pikachu',
            'attack' => 55,
            'speed' => 90
        ]);
    }
}