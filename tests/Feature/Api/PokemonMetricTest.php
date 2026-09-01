<?php

namespace Tests\Feature\Api;

use App\Models\Pokemon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PokemonMetricTest extends TestCase
{
    use RefreshDatabase; // Limpa o banco antes de cada teste

    public function test_nao_deve_permitir_acesso_ao_endpoint_sem_autenticacao(): void
    {
        $response = $this->getJson('/api/metrics/pokemons');
        
        $response->assertStatus(401);
    }

    public function test_deve_listar_pokemons_ordenados_pelo_maior_ataque(): void
    {
        // 1. Simula usuário logado
        Sanctum::actingAs(User::factory()->create());

        // 2. Cria os Pokémons falsos
        Pokemon::factory()->create(['name' => 'Bulbasaur', 'attack' => 49]);
        Pokemon::factory()->create(['name' => 'Machamp', 'attack' => 130]);

        // 3. Faz a requisição
        $response = $this->getJson('/api/metrics/pokemons?metric=attack&sort=desc');

        // 4. Valida se o Machamp (maior ataque) veio em primeiro
        $response->assertStatus(200);
        $this->assertEquals('Machamp', $response->json('data.0.name'));
    }
}