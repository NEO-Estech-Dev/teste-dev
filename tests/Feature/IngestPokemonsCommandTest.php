<?php

namespace Tests\Feature;

use App\Models\Pokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestPokemonsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingests_pokemons_from_pokeapi(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->fakeCatalog());

        $this->artisan('pokemons:ingest', ['--limit' => 1])
            ->assertSuccessful();

        $this->assertDatabaseHas('pokemons', [
            'pokeapi_id' => 1,
            'name' => 'bulbasaur',
            'hp' => 45,
            'special_attack' => 65,
        ]);

        $pokemon = Pokemon::query()->where('name', 'bulbasaur')->first();

        $this->assertNotNull($pokemon);
        $this->assertSame(['grass', 'poison'], $pokemon->types()->pluck('name')->all());
    }

    public function test_continues_when_a_pokemon_fails(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/pokemon?*' => Http::response([
                'results' => [
                    ['name' => 'missingno', 'url' => 'https://pokeapi.co/api/v2/pokemon/0/'],
                    ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                ],
            ]),
            '*/pokemon/missingno' => Http::response(['error' => 'Not Found'], 404),
            '*/pokemon/bulbasaur' => $this->fakeCatalog()['*/pokemon/bulbasaur'],
        ]);

        $this->artisan('pokemons:ingest', ['--limit' => 2])
            ->assertSuccessful();

        $this->assertDatabaseHas('pokemons', ['name' => 'bulbasaur']);
        $this->assertDatabaseMissing('pokemons', ['name' => 'missingno']);
    }

    public function test_fresh_option_clears_existing_records(): void
    {
        Pokemon::factory()->create(['name' => 'oldmon', 'pokeapi_id' => 999]);

        Http::preventStrayRequests();
        Http::fake($this->fakeCatalog());

        $this->artisan('pokemons:ingest', ['--limit' => 1, '--fresh' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('pokemons', ['name' => 'oldmon']);
        $this->assertDatabaseHas('pokemons', ['name' => 'bulbasaur']);
    }

    /**
     * @return array<string, Response>
     */
    private function fakeCatalog(): array
    {
        return [
            '*/pokemon?*' => Http::response([
                'results' => [
                    ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                ],
            ]),
            '*/pokemon/bulbasaur' => Http::response([
                'id' => 1,
                'name' => 'bulbasaur',
                'height' => 7,
                'weight' => 69,
                'sprites' => ['front_default' => 'https://example.test/bulbasaur.png'],
                'stats' => [
                    ['base_stat' => 45, 'stat' => ['name' => 'hp']],
                    ['base_stat' => 49, 'stat' => ['name' => 'attack']],
                    ['base_stat' => 49, 'stat' => ['name' => 'defense']],
                    ['base_stat' => 65, 'stat' => ['name' => 'special-attack']],
                    ['base_stat' => 65, 'stat' => ['name' => 'special-defense']],
                    ['base_stat' => 45, 'stat' => ['name' => 'speed']],
                ],
                'types' => [
                    ['slot' => 1, 'type' => ['name' => 'grass', 'url' => 'https://pokeapi.co/api/v2/type/12/']],
                    ['slot' => 2, 'type' => ['name' => 'poison', 'url' => 'https://pokeapi.co/api/v2/type/4/']],
                ],
            ]),
        ];
    }
}
