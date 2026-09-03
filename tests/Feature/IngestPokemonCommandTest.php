<?php

namespace Tests\Feature;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
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
