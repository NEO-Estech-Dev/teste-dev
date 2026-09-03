<?php

namespace Tests\Feature;

use App\Models\Pokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PokemonMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_paginated_metrics_with_default_parameters(): void
    {
        Pokemon::factory()->create(['name' => 'chansey', 'hp' => 250]);
        Pokemon::factory()->create(['name' => 'blissey', 'hp' => 255]);
        Pokemon::factory()->create(['name' => 'wobbuffet', 'hp' => 190]);

        $response = $this->getJson('/api/pokemons/metrics');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'blissey')
            ->assertJsonPath('data.0.value', 255)
            ->assertJsonPath('data.1.name', 'chansey')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['name', 'value'],
                ],
                'links',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_can_rank_by_another_metric(): void
    {
        Pokemon::factory()->create(['name' => 'alakazam', 'attack' => 50]);
        Pokemon::factory()->create(['name' => 'machamp', 'attack' => 130]);

        $this->getJson('/api/pokemons/metrics?metric=attack&order=desc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'machamp')
            ->assertJsonPath('data.0.value', 130);
    }

    public function test_can_filter_fields_and_sort_ascending(): void
    {
        Pokemon::factory()->create(['name' => 'blissey', 'hp' => 255]);
        Pokemon::factory()->create(['name' => 'sunkern', 'hp' => 30]);

        $response = $this->getJson('/api/pokemons/metrics?metric=hp&order=asc&fields=name&per_page=1');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'sunkern')
            ->assertJsonMissingPath('data.0.value')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_rejects_invalid_metric(): void
    {
        $this->getJson('/api/pokemons/metrics?metric=magic')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metric']);
    }

    public function test_rejects_invalid_fields_and_order(): void
    {
        $this->getJson('/api/pokemons/metrics?fields=sprite&order=best')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fields', 'order']);
    }
}
