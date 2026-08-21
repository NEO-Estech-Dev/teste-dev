<?php

namespace Tests\Feature\Api;

use App\Models\Pokemon;
use App\Models\PokemonStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PokemonMetricsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_consult_pokemon_metrics(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $pokemon = Pokemon::create([
            'external_id' => 25,
            'external_url' => 'https://pokeapi.co/api/v2/pokemon/25/',
            'name' => 'Pikachu',
            'stats_processed_at' => now(),
        ]);

        PokemonStat::create([
            'pokemon_id' => $pokemon->id,
            'stat_name' => 'hp',
            'base_stat' => 35,
            'effort' => 0,
        ]);

        $response = $this->getJson('/api/pokemons/metrics?metric=hp');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'message',
            'data',
        ]);

        $response->assertJsonFragment([
            'name' => 'Pikachu',
        ]);
    }
}