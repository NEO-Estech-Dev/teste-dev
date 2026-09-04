<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Pokemon;
use App\Models\PokemonStat;
use App\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IngestPokemonCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> URL fragment => canned response */
    private array $overrides = [];

    #[Test]
    public function it_ingests_pokemon_from_the_api(): void
    {
        $this->fakePokeApi();

        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();

        $this->assertSame(2, Pokemon::query()->count());
        $this->assertSame(12, PokemonStat::query()->count());
        $this->assertSame(3, Type::query()->count()); // grass, poison, fire

        $bulbasaur = Pokemon::query()->findOrFail(1);

        $this->assertSame('bulbasaur', $bulbasaur->name);
        $this->assertSame(7, $bulbasaur->height);
        $this->assertSame(69, $bulbasaur->weight);
        $this->assertSame(64, $bulbasaur->base_experience);
        $this->assertSame(318, $bulbasaur->stats_total);
        $this->assertSame(45, $bulbasaur->stats()->where('stat', 'hp')->value('base_stat'));
        $this->assertSame(['grass', 'poison'], $bulbasaur->types->pluck('name')->all());
    }

    #[Test]
    public function running_it_twice_does_not_duplicate_anything(): void
    {
        $this->fakePokeApi();

        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();
        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();

        $this->assertSame(2, Pokemon::query()->count());
        $this->assertSame(12, PokemonStat::query()->count());
        $this->assertSame(3, Type::query()->count());
        $this->assertSame(2, DB::table('pokemon_type')->where('pokemon_id', 1)->count());
    }

    #[Test]
    public function it_updates_records_that_changed_upstream(): void
    {
        $this->fakePokeApi();
        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();

        $payload = $this->fixture('bulbasaur');
        $payload['weight'] = 999;
        $payload['stats'][0]['base_stat'] = 100;

        $this->overrideResponse('/pokemon/1', Http::response($payload));
        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();

        $bulbasaur = Pokemon::query()->findOrFail(1);

        $this->assertSame(999, $bulbasaur->weight);
        $this->assertSame(100, $bulbasaur->stats()->where('stat', 'hp')->value('base_stat'));
        $this->assertSame(2, Pokemon::query()->count());
    }

    #[Test]
    public function it_removes_stats_and_types_that_no_longer_exist_upstream(): void
    {
        $this->fakePokeApi();
        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();

        $payload = $this->fixture('bulbasaur');
        $payload['stats'] = array_values(array_filter(
            $payload['stats'],
            static fn (array $stat): bool => $stat['stat']['name'] !== 'speed',
        ));
        $payload['types'] = array_values(array_filter(
            $payload['types'],
            static fn (array $type): bool => $type['type']['name'] !== 'poison',
        ));

        $this->overrideResponse('/pokemon/1', Http::response($payload));
        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();

        $bulbasaur = Pokemon::query()->findOrFail(1);

        $this->assertSame(5, $bulbasaur->stats()->count());
        $this->assertSame(['grass'], $bulbasaur->types()->pluck('name')->all());
        $this->assertDatabaseMissing('pokemon_stats', [
            'pokemon_id' => 1,
            'stat' => 'speed',
        ]);
    }

    #[Test]
    public function it_fails_when_a_resource_cannot_be_fetched(): void
    {
        // The retry backoff would otherwise make this test sleep for real.
        Sleep::fake();

        $this->fakePokeApi(['/pokemon/4' => Http::response(status: 500)]);

        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertFailed();

        // A partial ingestion still persists what it could read.
        $this->assertSame(1, Pokemon::query()->count());
        $this->assertDatabaseHas('pokemons', ['name' => 'bulbasaur']);
    }

    #[Test]
    public function fresh_wipes_previously_ingested_rows(): void
    {
        $this->fakePokeApi();
        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();

        Pokemon::factory()->withStats()->create(['id' => 9999, 'name' => 'stale-entry']);

        $this->artisan('pokemon:ingest', ['--limit' => 2, '--fresh' => true])->assertSuccessful();

        $this->assertSame(2, Pokemon::query()->count());
        $this->assertDatabaseMissing('pokemons', ['name' => 'stale-entry']);
    }

    #[Test]
    public function it_invalidates_the_metrics_cache(): void
    {
        $this->fakePokeApi();
        Cache::tags([(string) config('metrics.cache_tag')])->put('cached-ranking', true, 300);

        $this->artisan('pokemon:ingest', ['--limit' => 2])->assertSuccessful();

        $this->assertNull(Cache::tags([(string) config('metrics.cache_tag')])->get('cached-ranking'));
    }

    /**
     * @param  array<string, mixed>  $overrides  URL fragment => canned response
     */
    private function fakePokeApi(array $overrides = []): void
    {
        $this->overrides = $overrides;

        // Http::fake() merges stub callbacks rather than replacing them, so the
        // overrides have to live in mutable state instead of a second fake().
        Http::fake(function (Request $request) {
            $url = $request->url();

            foreach ($this->overrides as $fragment => $response) {
                if (str_contains($url, $fragment)) {
                    return $response;
                }
            }

            return match (true) {
                str_contains($url, '/pokemon/1') => Http::response($this->fixture('bulbasaur')),
                str_contains($url, '/pokemon/4') => Http::response($this->fixture('charmander')),
                default => Http::response($this->fixture('index')),
            };
        });
    }

    private function overrideResponse(string $fragment, mixed $response): void
    {
        $this->overrides[$fragment] = $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(string $name): array
    {
        return json_decode(
            (string) file_get_contents(base_path("tests/Fixtures/pokeapi/{$name}.json")),
            associative: true,
        );
    }
}
