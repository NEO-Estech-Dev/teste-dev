<?php

namespace Tests\Feature;

use App\Models\Pokemon;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_ingests_multiple_pokemons_in_parallel(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/pokemon?*' => Http::response([
                'results' => [
                    ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                    ['name' => 'ivysaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/2/'],
                ],
            ]),
            '*/pokemon/bulbasaur' => $this->fakePokemon('bulbasaur', 1, 45),
            '*/pokemon/ivysaur' => $this->fakePokemon('ivysaur', 2, 60),
        ]);

        $this->artisan('pokemons:ingest', ['--limit' => 2, '--concurrency' => 2])
            ->assertSuccessful();

        $this->assertDatabaseHas('pokemons', ['name' => 'bulbasaur', 'hp' => 45]);
        $this->assertDatabaseHas('pokemons', ['name' => 'ivysaur', 'hp' => 60]);
        $this->assertSame(2, Pokemon::query()->count());
    }

    /**
     * @return array<string, PromiseInterface>
     */
    private function fakeCatalog(): array
    {
        return [
            '*/pokemon?*' => Http::response([
                'results' => [
                    ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                ],
            ]),
            '*/pokemon/bulbasaur' => $this->fakePokemon('bulbasaur', 1, 45),
        ];
    }

    private function fakePokemon(string $name, int $id, int $hp): PromiseInterface
    {
        return Http::response([
            'id' => $id,
            'name' => $name,
            'height' => 7,
            'weight' => 69,
            'sprites' => ['front_default' => "https://example.test/{$name}.png"],
            'stats' => [
                ['base_stat' => $hp, 'stat' => ['name' => 'hp']],
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
        ]);
    }
}
