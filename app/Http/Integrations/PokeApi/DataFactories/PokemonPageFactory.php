<?php

declare(strict_types=1);

namespace App\Http\Integrations\PokeApi\DataFactories;

use App\Http\Integrations\PokeApi\DataObjects\PokemonPage;
use App\Http\Integrations\PokeApi\Exceptions\InvalidPokeApiPayload;

final readonly class PokemonPageFactory
{
    public function __construct(private PokemonReferenceFactory $references) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function make(array $attributes): PokemonPage
    {
        $results = $attributes['results'] ?? null;
        $next = $attributes['next'] ?? null;
        $total = $attributes['count'] ?? null;

        if (! is_array($results)) {
            throw InvalidPokeApiPayload::missingOrInvalid('results');
        }

        if (! array_key_exists('next', $attributes) || ($next !== null && (! is_string($next) || $next === ''))) {
            throw InvalidPokeApiPayload::missingOrInvalid('next');
        }

        if (! is_int($total) || $total < 0) {
            throw InvalidPokeApiPayload::missingOrInvalid('count');
        }

        return new PokemonPage(
            items: $this->references->collection(array_values($results)),
            hasNextPage: $next !== null,
            total: $total,
        );
    }
}
