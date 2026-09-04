<?php

declare(strict_types=1);

use App\Http\Integrations\PokeApi\DataFactories\PokemonDetailsFactory;
use App\Http\Integrations\PokeApi\DataObjects\PokemonDetails;

it('maps a PokeAPI payload into a typed data object', function () {
    $details = app(PokemonDetailsFactory::class)->make(pokemonApiPayload());

    expect($details)
        ->toBeInstanceOf(PokemonDetails::class)
        ->pokeapiId->toBe(1)
        ->name->toBe('bulbasaur')
        ->height->toBe(7)
        ->weight->toBe(69)
        ->baseExperience->toBe(64)
        ->hp->toBe(45)
        ->attack->toBe(49)
        ->defense->toBe(49)
        ->specialAttack->toBe(65)
        ->specialDefense->toBe(65)
        ->speed->toBe(45)
        ->and($details->toArray())->toBe([
            'pokeapiId' => 1,
            'name' => 'bulbasaur',
            'height' => 7,
            'weight' => 69,
            'baseExperience' => 64,
            'hp' => 45,
            'attack' => 49,
            'defense' => 49,
            'specialAttack' => 65,
            'specialDefense' => 65,
            'speed' => 45,
        ]);
});

it('accepts a null base experience', function () {
    $payload = pokemonApiPayload();
    $payload['base_experience'] = null;

    expect(app(PokemonDetailsFactory::class)->make($payload)->baseExperience)->toBeNull();
});

it('maps contract values without applying semantic validation', function () {
    $payload = pokemonApiPayload();
    $payload['id'] = 0;
    $payload['name'] = '';
    $payload['height'] = -1;
    $payload['weight'] = -2;

    expect(app(PokemonDetailsFactory::class)->make($payload))
        ->pokeapiId->toBe(0)
        ->name->toBe('')
        ->height->toBe(-1)
        ->weight->toBe(-2);
});

it('fails conversion when a required stat is missing', function () {
    $payload = pokemonApiPayload();
    array_pop($payload['stats']);

    expect(fn (): PokemonDetails => app(PokemonDetailsFactory::class)->make($payload))
        ->toThrow(ErrorException::class, 'Undefined array key "attack"');
});

it('stops collection conversion when an item cannot be converted', function () {
    $invalid = pokemonApiPayload(2, 'ivysaur');
    $invalid['name'] = [];

    expect(fn () => app(PokemonDetailsFactory::class)->collection([
        pokemonApiPayload(),
        $invalid,
    ]))->toThrow(InvalidArgumentException::class);
});
