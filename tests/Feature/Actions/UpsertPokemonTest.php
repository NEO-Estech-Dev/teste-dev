<?php

declare(strict_types=1);

use App\Actions\UpsertPokemon;
use App\Http\Integrations\PokeApi\DataFactories\PokemonDetailsFactory;
use App\Models\Pokemon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

it('persists a complete batch with one upsert statement', function () {
    $upserts = 0;

    DB::listen(function (QueryExecuted $query) use (&$upserts): void {
        if (str_starts_with($query->sql, 'insert into "pokemons"')) {
            $upserts++;
        }
    });

    $factory = app(PokemonDetailsFactory::class);

    app(UpsertPokemon::class)->handle(collect([
        $factory->make(pokemonApiPayload()),
        $factory->make(pokemonApiPayload(2, 'ivysaur')),
    ]));

    expect(Pokemon::query()->count())->toBe(2)
        ->and($upserts)->toBe(1);
});

it('updates attributes while preserving the creation timestamp', function () {
    $firstTimestamp = CarbonImmutable::parse('2026-01-01 12:00:00');
    Date::setTestNow($firstTimestamp);

    $action = app(UpsertPokemon::class);
    $factory = app(PokemonDetailsFactory::class);
    $action->handle(collect([$factory->make(pokemonApiPayload())]));

    Date::setTestNow($firstTimestamp->addDay());
    $updatedPayload = pokemonApiPayload();
    $updatedPayload['name'] = 'updated-bulbasaur';
    $updatedPayload['height'] = 8;

    $action->handle(collect([$factory->make($updatedPayload)]));

    $pokemon = Pokemon::query()->sole();

    expect($pokemon)
        ->name->toBe('updated-bulbasaur')
        ->height->toBe(8)
        ->and($pokemon->created_at?->toDateTimeString())->toBe($firstTimestamp->toDateTimeString())
        ->and($pokemon->updated_at?->toDateTimeString())->toBe($firstTimestamp->addDay()->toDateTimeString())
        ->and(Pokemon::query()->count())->toBe(1);
});
