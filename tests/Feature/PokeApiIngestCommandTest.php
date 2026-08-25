<?php

namespace Tests\Feature;

use App\Jobs\IngestPokemonChunkJob;
use App\Services\PokeApiIngestionService;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PokeApiIngestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_ingests_pokeapi_data_and_is_idempotent(): void
    {
        Http::fake(function (Request $request) {
            $url = rtrim(strtok($request->url(), '?'), '/');

            return match ($url) {
                'https://pokeapi.co/api/v2/pokemon' => Http::response([
                    'count' => 2,
                    'results' => [
                        ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                        ['name' => 'ivysaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/2/'],
                    ],
                ]),
                'https://pokeapi.co/api/v2/pokemon/1' => Http::response($this->pokemonPayload(
                    id: 1,
                    name: 'bulbasaur',
                    speciesId: 1,
                    species: 'bulbasaur',
                    hp: 45,
                    attack: 49,
                    type: 'grass',
                    ability: 'overgrow',
                )),
                'https://pokeapi.co/api/v2/pokemon/2' => Http::response($this->pokemonPayload(
                    id: 2,
                    name: 'ivysaur',
                    speciesId: 1,
                    species: 'bulbasaur',
                    hp: 60,
                    attack: 62,
                    type: 'grass',
                    ability: 'overgrow',
                )),
                'https://pokeapi.co/api/v2/pokemon-species/1' => Http::response([
                    'id' => 1,
                    'name' => 'bulbasaur',
                    'base_happiness' => 50,
                    'capture_rate' => 45,
                    'is_baby' => false,
                    'is_legendary' => false,
                    'is_mythical' => false,
                ]),
                default => Http::response([], 404),
            };
        });

        $this->artisan('pokeapi:ingest --limit=2 --chunk=2')
            ->assertSuccessful();

        $this->assertDatabaseCount('pokemons', 2);
        $this->assertDatabaseCount('species', 1);
        $this->assertDatabaseCount('stats', 2);
        $this->assertDatabaseCount('types', 1);
        $this->assertDatabaseCount('abilities', 1);
        $this->assertDatabaseCount('pokemon_stats', 4);
        $this->assertDatabaseCount('pokemon_type', 2);
        $this->assertDatabaseCount('pokemon_ability', 2);

        $this->artisan('pokeapi:ingest --limit=2 --chunk=2')
            ->assertSuccessful();

        $this->assertDatabaseCount('pokemons', 2);
        $this->assertDatabaseCount('species', 1);
        $this->assertDatabaseCount('stats', 2);
        $this->assertDatabaseCount('types', 1);
        $this->assertDatabaseCount('abilities', 1);
        $this->assertDatabaseCount('pokemon_stats', 4);
        $this->assertDatabaseCount('pokemon_type', 2);
        $this->assertDatabaseCount('pokemon_ability', 2);

        $this->artisan('pokeapi:ingest --fresh --limit=2 --chunk=2')
            ->assertSuccessful();

        $this->assertDatabaseCount('pokemons', 2);
        $this->assertDatabaseCount('species', 1);
        $this->assertDatabaseCount('stats', 2);
        $this->assertDatabaseCount('types', 1);
        $this->assertDatabaseCount('abilities', 1);
        $this->assertDatabaseCount('pokemon_stats', 4);
        $this->assertDatabaseCount('pokemon_type', 2);
        $this->assertDatabaseCount('pokemon_ability', 2);
    }

    public function test_command_dispatches_async_batch_with_expected_chunks(): void
    {
        Bus::fake();
        Cache::lock('pokeapi-ingestion')->forceRelease();

        $this->artisan('pokeapi:ingest --async --limit=120 --offset=10 --chunk=50')
            ->assertSuccessful();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch): bool {
            /** @var Collection<int, IngestPokemonChunkJob> $jobs */
            $jobs = $batch->jobs;

            return $batch->name === 'Ingestão PokeAPI'
                && $batch->connection() === 'redis'
                && $batch->queue() === 'pokeapi-ingestion'
                && $jobs->count() === 3
                && $jobs[0]->offset === 10
                && $jobs[0]->limit === 50
                && $jobs[1]->offset === 60
                && $jobs[1]->limit === 50
                && $jobs[2]->offset === 110
                && $jobs[2]->limit === 20;
        });

        Cache::lock('pokeapi-ingestion')->forceRelease();
    }

    public function test_async_command_uses_pokeapi_count_when_limit_is_not_provided(): void
    {
        Bus::fake();
        Cache::lock('pokeapi-ingestion')->forceRelease();

        Http::fake([
            'https://pokeapi.co/api/v2/pokemon*' => Http::response([
                'count' => 125,
                'results' => [],
            ]),
        ]);

        $this->artisan('pokeapi:ingest --async --offset=25 --chunk=50')
            ->assertSuccessful();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch): bool {
            /** @var Collection<int, IngestPokemonChunkJob> $jobs */
            $jobs = $batch->jobs;

            return $jobs->count() === 2
                && $jobs[0]->offset === 25
                && $jobs[0]->limit === 50
                && $jobs[1]->offset === 75
                && $jobs[1]->limit === 50;
        });

        Cache::lock('pokeapi-ingestion')->forceRelease();
    }

    public function test_ingest_pokemon_chunk_job_delegates_processing_to_service(): void
    {
        $service = Mockery::mock(PokeApiIngestionService::class);
        $service
            ->shouldReceive('ingestChunk')
            ->once()
            ->with(30, 25)
            ->andReturn([
                'processed' => 25,
                'pages' => 1,
                'failed' => 0,
            ]);

        (new IngestPokemonChunkJob(offset: 30, limit: 25))->handle($service);
    }

    /**
     * @return array<string, mixed>
     */
    private function pokemonPayload(
        int $id,
        string $name,
        int $speciesId,
        string $species,
        int $hp,
        int $attack,
        string $type,
        string $ability,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'height' => 7 + $id,
            'weight' => 60 + $id,
            'base_experience' => 50 + $id,
            'species' => [
                'name' => $species,
                'url' => "https://pokeapi.co/api/v2/pokemon-species/{$speciesId}/",
            ],
            'stats' => [
                [
                    'base_stat' => $hp,
                    'effort' => 0,
                    'stat' => ['name' => 'hp', 'url' => 'https://pokeapi.co/api/v2/stat/1/'],
                ],
                [
                    'base_stat' => $attack,
                    'effort' => 0,
                    'stat' => ['name' => 'attack', 'url' => 'https://pokeapi.co/api/v2/stat/2/'],
                ],
            ],
            'types' => [
                [
                    'slot' => 1,
                    'type' => ['name' => $type, 'url' => 'https://pokeapi.co/api/v2/type/12/'],
                ],
            ],
            'abilities' => [
                [
                    'is_hidden' => false,
                    'slot' => 1,
                    'ability' => ['name' => $ability, 'url' => 'https://pokeapi.co/api/v2/ability/65/'],
                ],
            ],
        ];
    }
}
