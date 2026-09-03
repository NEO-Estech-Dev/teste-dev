<?php

namespace Tests\Feature;

use App\Models\Pokemon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PokemonMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_endpoint_requires_authentication(): void
    {
        $this->get('/api/v1/pokemon/metrics')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Não autenticado.']);
    }

    public function test_it_returns_hp_descending_by_default(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Pokemon::factory()->create(['name' => 'bulbasaur', 'hp' => 45]);
        Pokemon::factory()->create(['name' => 'chansey', 'hp' => 250]);

        $response = $this->getJson('/api/v1/pokemon/metrics');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'chansey')
            ->assertJsonPath('data.0.metric', 250)
            ->assertJsonPath('data.1.name', 'bulbasaur')
            ->assertJsonPath('meta.metric', 'hp')
            ->assertJsonPath('meta.direction', 'desc')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_it_supports_metric_direction_fields_and_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Pokemon::factory()->create(['name' => 'snorlax', 'speed' => 30]);
        Pokemon::factory()->create(['name' => 'electrode', 'speed' => 150]);

        $response = $this->getJson('/api/v1/pokemon/metrics?metric=speed&direction=asc&fields=name&per_page=1');

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [['name' => 'snorlax']],
                'meta' => [
                    'metric' => 'speed',
                    'direction' => 'asc',
                    'current_page' => 1,
                    'per_page' => 1,
                    'total' => 2,
                    'last_page' => 2,
                ],
                'links' => [
                    'first' => url('/api/v1/pokemon/metrics?metric=speed&direction=asc&fields=name&per_page=1&page=1'),
                    'last' => url('/api/v1/pokemon/metrics?metric=speed&direction=asc&fields=name&per_page=1&page=2'),
                    'prev' => null,
                    'next' => url('/api/v1/pokemon/metrics?metric=speed&direction=asc&fields=name&per_page=1&page=2'),
                ],
            ]);
    }

    public function test_it_rejects_unknown_metrics_and_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/pokemon/metrics?metric=drop_table&fields=name,password')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metric', 'fields']);
    }

    public function test_it_returns_different_records_on_the_second_page(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Pokemon::factory()->create(['name' => 'slow', 'speed' => 10]);
        Pokemon::factory()->create(['name' => 'medium', 'speed' => 20]);
        Pokemon::factory()->create(['name' => 'fast', 'speed' => 30]);

        $firstPage = $this->getJson('/api/v1/pokemon/metrics?metric=speed&fields=name&per_page=2&page=1');
        $secondPage = $this->getJson('/api/v1/pokemon/metrics?metric=speed&fields=name&per_page=2&page=2');

        $firstPage->assertOk()->assertJsonPath('data.0.name', 'fast');
        $secondPage
            ->assertOk()
            ->assertJsonPath('data.0.name', 'slow')
            ->assertJsonPath('meta.current_page', 2);

        $this->assertNotSame($firstPage->json('data'), $secondPage->json('data'));
    }

    public function test_it_uses_the_id_as_a_stable_tie_breaker(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $first = Pokemon::factory()->create(['name' => 'first', 'hp' => 100]);
        $second = Pokemon::factory()->create(['name' => 'second', 'hp' => 100]);

        $this->getJson('/api/v1/pokemon/metrics?fields=pokeapi_id,name,metric')
            ->assertOk()
            ->assertJsonPath('data.0.pokeapi_id', $second->pokeapi_id)
            ->assertJsonPath('data.1.pokeapi_id', $first->pokeapi_id);
    }

    public function test_it_can_rank_by_every_supported_metric(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $metrics = ['hp', 'attack', 'defense', 'special_attack', 'special_defense', 'speed'];
        $lowerValues = array_fill_keys($metrics, 10);
        $higherValues = array_fill_keys($metrics, 20);

        Pokemon::factory()->create(['name' => 'lower', ...$lowerValues]);
        Pokemon::factory()->create(['name' => 'higher', ...$higherValues]);

        foreach ($metrics as $metric) {
            $this->getJson("/api/v1/pokemon/metrics?metric={$metric}")
                ->assertOk()
                ->assertJsonPath('data.0.name', 'higher')
                ->assertJsonPath('data.0.metric', 20)
                ->assertJsonPath('meta.metric', $metric);
        }
    }
}
