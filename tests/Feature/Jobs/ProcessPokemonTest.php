<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessPokemon;
use App\Models\Pokemon;
use App\Models\PokemonStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessPokemonTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_processes_pokemon_data(): void
    {
        $pokemon = Pokemon::create([
            'external_id' => 25,
            'external_url' => 'https://pokeapi.co/api/v2/pokemon/25/',
            'name' => 'pikachu',
        ]);

        Http::fake([
            $pokemon->external_url => Http::response([
                'height' => 4,
                'weight' => 60,
                'stats' => [
                    [
                        'base_stat' => 35,
                        'effort' => 0,
                        'stat' => [
                            'name' => 'hp',
                        ],
                    ],
                    [
                        'base_stat' => 90,
                        'effort' => 2,
                        'stat' => [
                            'name' => 'speed',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $job = new ProcessPokemon($pokemon->id);

        $job->handle();

        $this->assertDatabaseHas('pokemons', [
            'id' => $pokemon->id,
            'height' => 4,
            'weight' => 60,
        ]);

        $this->assertDatabaseHas('pokemons_stats', [
            'pokemon_id' => $pokemon->id,
            'stat_name' => 'hp',
            'base_stat' => 35,
            'effort' => 0,
        ]);

        $this->assertDatabaseHas('pokemons_stats', [
            'pokemon_id' => $pokemon->id,
            'stat_name' => 'speed',
            'base_stat' => 90,
            'effort' => 2,
        ]);

        $this->assertNotNull(
            Pokemon::find($pokemon->id)->stats_processed_at
        );
    }
}