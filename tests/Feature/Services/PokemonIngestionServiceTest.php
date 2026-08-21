<?php

namespace Tests\Feature\Services;

use App\Jobs\ProcessPokemon;
use App\Models\Pokemon;
use App\Services\PokemonApiService;
use App\Services\PokemonIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PokemonIngestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingestion_creates_pokemons_and_dispatches_processing_jobs(): void
    {
        Http::fake([
            'https://pokeapi.co/api/v2/pokemon/*' => Http::response([
                'count' => 2,
                'next' => null,
                'previous' => null,
                'results' => [
                    [
                        'name' => 'bulbasaur',
                        'url' => 'https://pokeapi.co/api/v2/pokemon/1/',
                    ],
                    [
                        'name' => 'ivysaur',
                        'url' => 'https://pokeapi.co/api/v2/pokemon/2/',
                    ],
                ],
            ], 200),
        ]);

        Queue::fake();

        $service = app(PokemonIngestionService::class);

        $service->handle();

        $this->assertDatabaseHas('pokemons', [
            'external_id' => 1,
            'name' => 'bulbasaur',
        ]);

        $this->assertDatabaseHas('pokemons', [
            'external_id' => 2,
            'name' => 'ivysaur',
        ]);

        Queue::assertPushed(ProcessPokemon::class, 2);
    }
}