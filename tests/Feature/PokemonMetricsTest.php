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
}
