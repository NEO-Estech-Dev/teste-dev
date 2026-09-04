<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Pokemon;
use App\Models\PokemonStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PokemonMetricsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_requires_authentication(): void
    {
        $this->getJson('/api/v1/pokemons/metrics')->assertUnauthorized();
    }

    #[Test]
    public function it_ranks_by_hp_descending_by_default(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        $response = $this->getJson('/api/v1/pokemons/metrics');

        $response->assertOk()
            ->assertJsonPath('meta.metric', 'hp')
            ->assertJsonPath('meta.order', 'desc')
            ->assertJsonPath('meta.limit', 10)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.name', 'chansey')
            ->assertJsonPath('data.0.value', 250)
            ->assertJsonPath('data.2.name', 'shedinja');

        // Default projection is name + value, nothing else.
        $this->assertSame(['name', 'value'], array_keys($response->json('data.0')));
    }

    #[Test]
    public function it_ranks_the_worst_first_when_asked(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        $this->getJson('/api/v1/pokemons/metrics?order=worst')
            ->assertOk()
            ->assertJsonPath('meta.order', 'asc')
            ->assertJsonPath('data.0.name', 'shedinja')
            ->assertJsonPath('data.0.value', 1);
    }

    #[Test]
    public function it_accepts_the_underscore_spelling_of_a_stat(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        $this->getJson('/api/v1/pokemons/metrics?metric=special_attack')
            ->assertOk()
            ->assertJsonPath('meta.metric', 'special-attack')
            ->assertJsonPath('data.0.name', 'alakazam');
    }

    #[Test]
    public function it_returns_only_the_requested_fields(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        $response = $this->getJson('/api/v1/pokemons/metrics?metric=speed&fields=id,name,types&limit=1');

        $response->assertOk();

        $this->assertSame(['id', 'name', 'types'], array_keys($response->json('data.0')));
        $this->assertSame('alakazam', $response->json('data.0.name'));
        $this->assertSame(['psychic'], $response->json('data.0.types'));
    }

    #[Test]
    public function it_can_rank_by_a_column_metric(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        $this->getJson('/api/v1/pokemons/metrics?metric=weight&fields=name,value')
            ->assertOk()
            ->assertJsonPath('meta.metric', 'weight')
            ->assertJsonPath('data.0.name', 'alakazam')
            ->assertJsonPath('data.0.value', 480);
    }

    #[Test]
    public function it_skips_rows_without_a_value_for_a_nullable_metric(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        Pokemon::factory()->withStats()->create([
            'name' => 'missingno',
            'base_experience' => null,
        ]);

        $response = $this->getJson('/api/v1/pokemons/metrics?metric=base_experience&order=asc');

        $response->assertOk()->assertJsonPath('meta.total', 3);
        $this->assertNotContains('missingno', array_column($response->json('data'), 'name'));
    }

    #[Test]
    public function it_filters_by_type(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        $this->getJson('/api/v1/pokemons/metrics?type=psychic')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'alakazam');
    }

    #[Test]
    public function it_can_exclude_alternate_forms(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        Pokemon::factory()->alternateForm()->withStats(['hp' => 999])->create(['name' => 'mega-chansey']);

        $this->getJson('/api/v1/pokemons/metrics?only_default=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.name', 'chansey');
    }

    #[Test]
    public function it_accepts_boolean_words_for_the_default_form_filter(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        Pokemon::factory()->alternateForm()->withStats(['hp' => 999])->create(['name' => 'mega-chansey']);

        $this->getJson('/api/v1/pokemons/metrics?only_default=true')
            ->assertOk()
            ->assertJsonPath('meta.only_default', true)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.name', 'chansey');

        $this->getJson('/api/v1/pokemons/metrics?only_default=false')
            ->assertOk()
            ->assertJsonPath('meta.only_default', false)
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('data.0.name', 'mega-chansey');
    }

    #[Test]
    public function it_paginates(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        $this->getJson('/api/v1/pokemons/metrics?limit=2&page=2')
            ->assertOk()
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'shedinja');
    }

    #[Test]
    public function it_caches_a_ranking(): void
    {
        $this->actingAsUser();
        $this->seedPokedex();

        $this->getJson('/api/v1/pokemons/metrics')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'chansey');

        PokemonStat::query()
            ->where('pokemon_id', 113)
            ->where('stat', 'hp')
            ->update(['base_stat' => 0]);

        $this->getJson('/api/v1/pokemons/metrics')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'chansey');
    }

    #[Test]
    public function it_rejects_an_unknown_metric(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/v1/pokemons/metrics?metric=banana')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('metric');
    }

    #[Test]
    public function it_rejects_an_unknown_field(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/v1/pokemons/metrics?fields=name,password')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fields.1');
    }

    #[Test]
    public function it_rejects_a_limit_above_the_cap(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/v1/pokemons/metrics?limit=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    }

    private function actingAsUser(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    private function seedPokedex(): void
    {
        Pokemon::factory()
            ->withStats(['hp' => 250, 'attack' => 5, 'special-attack' => 35, 'speed' => 50])
            ->withTypes(['normal'])
            ->create([
                'id' => 113,
                'name' => 'chansey',
                'height' => 11,
                'weight' => 346,
                'base_experience' => 395,
            ]);

        Pokemon::factory()
            ->withStats(['hp' => 55, 'attack' => 50, 'special-attack' => 135, 'speed' => 120])
            ->withTypes(['psychic'])
            ->create([
                'id' => 65,
                'name' => 'alakazam',
                'height' => 15,
                'weight' => 480,
                'base_experience' => 250,
            ]);

        Pokemon::factory()
            ->withStats(['hp' => 1, 'attack' => 90, 'special-attack' => 30, 'speed' => 40])
            ->withTypes(['bug', 'ghost'])
            ->create([
                'id' => 292,
                'name' => 'shedinja',
                'height' => 8,
                'weight' => 12,
                'base_experience' => 83,
            ]);
    }
}
