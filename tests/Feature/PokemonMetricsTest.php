<?php

namespace Tests\Feature;

use App\Models\Pokemon;
use App\Models\Stat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PokemonMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_endpoint_requires_token(): void
    {
        $this->getJson('/api/metrics/pokemon')
            ->assertUnauthorized();

        $this->get('/api/metrics/pokemon')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_metrics_endpoint_returns_default_hp_ranking(): void
    {
        $this->seedPokemonMetrics();
        $token = User::factory()->create()->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/metrics/pokemon?limit=3')
            ->assertOk()
            ->assertJsonPath('metric', 'hp')
            ->assertJsonPath('meta.field', 'name')
            ->assertJsonPath('meta.order', 'desc')
            ->assertJsonPath('data.0.name', 'charizard')
            ->assertJsonPath('data.1.name', 'bulbasaur')
            ->assertJsonPath('data.2.name', 'pikachu');
    }

    public function test_metrics_endpoint_supports_field_order_metric_limit_and_page(): void
    {
        $this->seedPokemonMetrics();
        $token = User::factory()->create()->createToken('api-token')->plainTextToken;

        $response = $this->withToken($token)->getJson(
            '/api/metrics/pokemon?metric=attack&field=height&order=asc&limit=2&page=1',
        );

        $response
            ->assertOk()
            ->assertJsonPath('metric', 'attack')
            ->assertJsonPath('meta.field', 'height')
            ->assertJsonPath('meta.order', 'asc')
            ->assertJsonPath('meta.ordered_by', 'metric_value')
            ->assertJsonPath('meta.limit', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.height', 7)
            ->assertJsonPath('data.1.height', 4);

        $this->assertSame(['height'], array_keys($response->json('data.0')));
    }

    public function test_metrics_endpoint_validates_filters(): void
    {
        $token = User::factory()->create()->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/metrics/pokemon?metric=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('metric');

        $this->withToken($token)
            ->getJson('/api/metrics/pokemon?field=password')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('field');

        $this->withToken($token)
            ->getJson('/api/metrics/pokemon?order=random')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');

        $this->withToken($token)
            ->getJson('/api/metrics/pokemon?limit=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    }

    private function seedPokemonMetrics(): void
    {
        $hp = Stat::query()->create(['pokeapi_id' => 1, 'name' => 'hp']);
        $attack = Stat::query()->create(['pokeapi_id' => 2, 'name' => 'attack']);

        $bulbasaur = Pokemon::query()->create([
            'pokeapi_id' => 1,
            'name' => 'bulbasaur',
            'height' => 7,
            'weight' => 69,
            'base_experience' => 64,
        ]);
        $bulbasaur->stats()->attach($hp->id, ['base_stat' => 45, 'effort' => 0]);
        $bulbasaur->stats()->attach($attack->id, ['base_stat' => 49, 'effort' => 0]);

        $charizard = Pokemon::query()->create([
            'pokeapi_id' => 6,
            'name' => 'charizard',
            'height' => 17,
            'weight' => 905,
            'base_experience' => 240,
        ]);
        $charizard->stats()->attach($hp->id, ['base_stat' => 78, 'effort' => 0]);
        $charizard->stats()->attach($attack->id, ['base_stat' => 84, 'effort' => 0]);

        $pikachu = Pokemon::query()->create([
            'pokeapi_id' => 25,
            'name' => 'pikachu',
            'height' => 4,
            'weight' => 60,
            'base_experience' => 112,
        ]);
        $pikachu->stats()->attach($hp->id, ['base_stat' => 35, 'effort' => 0]);
        $pikachu->stats()->attach($attack->id, ['base_stat' => 55, 'effort' => 0]);
    }
}
