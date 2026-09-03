<?php

namespace Tests\Feature;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class IngestPokemonCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_ingests_and_updates_pokemon_without_duplicates(): void
    {
        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => $this->responseFor($request));

        $this->artisan('pokemon:ingest', ['--limit' => 2])
            ->assertSuccessful();
        $this->artisan('pokemon:ingest', ['--limit' => 2])
            ->assertSuccessful();

        $this->assertDatabaseCount('pokemon', 2);
        $this->assertDatabaseHas('pokemon', [
            'pokeapi_id' => 1,
            'name' => 'bulbasaur',
            'hp' => 45,
            'special_attack' => 65,
        ]);
    }

    public function test_command_rejects_invalid_options(): void
    {
        foreach ([
            ['--limit' => -1],
            ['--start' => -1],
            ['--batch' => 0],
            ['--batch' => 51],
            ['--concurrency' => 0],
            ['--concurrency' => 11],
        ] as $options) {
            $this->artisan('pokemon:ingest', $options)
                ->assertExitCode(Command::INVALID);
        }
    }

    public function test_command_preserves_completed_batches_and_reports_the_resume_offset(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request): PromiseInterface|Response {
            $url = $request->url();

            if (str_ends_with($url, '/pokemon/3/')) {
                return Http::response(['message' => 'temporary failure'], 503);
            }

            if (preg_match('~/pokemon/(\d+)/?$~', $url, $matches)) {
                $id = (int) $matches[1];

                return Http::response($this->pokemonPayload($id, "pokemon-{$id}"));
            }

            $limit = (int) ($request->data()['limit'] ?? 1);
            $offset = (int) ($request->data()['offset'] ?? 0);

            if ($limit === 1) {
                return Http::response(['count' => 4, 'results' => []]);
            }

            return Http::response([
                'count' => 4,
                'results' => collect(range($offset + 1, $offset + $limit))->map(fn (int $id): array => [
                    'name' => "pokemon-{$id}",
                    'url' => "https://pokeapi.co/api/v2/pokemon/{$id}/",
                ])->all(),
            ]);
        });

        $this->artisan('pokemon:ingest', ['--limit' => 4, '--batch' => 2])
            ->expectsOutputToContain('Continue com: php artisan pokemon:ingest --start=2')
            ->assertFailed();

        $this->assertDatabaseCount('pokemon', 2);
        $this->assertDatabaseHas('pokemon', ['pokeapi_id' => 1]);
        $this->assertDatabaseHas('pokemon', ['pokeapi_id' => 2]);
        $this->assertDatabaseMissing('pokemon', ['pokeapi_id' => 3]);
    }

    private function responseFor(Request $request): PromiseInterface|Response
    {
        $url = $request->url();

        if (preg_match('~/pokemon/(\d+)/?$~', $url, $matches)) {
            $id = (int) $matches[1];

            return Http::response($this->pokemonPayload($id, $id === 1 ? 'bulbasaur' : 'ivysaur'));
        }

        $limit = (int) ($request->data()['limit'] ?? 1);

        return Http::response([
            'count' => 2,
            'results' => $limit === 1 ? [] : [
                ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                ['name' => 'ivysaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/2/'],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function pokemonPayload(int $id, string $name): array
    {
        $stats = [
            'hp' => 44 + $id,
            'attack' => 48 + $id,
            'defense' => 48 + $id,
            'special-attack' => 64 + $id,
            'special-defense' => 64 + $id,
            'speed' => 44 + $id,
        ];

        return [
            'id' => $id,
            'name' => $name,
            'height' => 7,
            'weight' => 69,
            'base_experience' => 64,
            'types' => [['type' => ['name' => 'grass']]],
            'stats' => collect($stats)->map(fn (int $value, string $stat): array => [
                'base_stat' => $value,
                'stat' => ['name' => $stat],
            ])->values()->all(),
        ];
    }
}
