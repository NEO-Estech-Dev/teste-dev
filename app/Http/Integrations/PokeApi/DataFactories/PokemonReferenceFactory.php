<?php

declare(strict_types=1);

namespace App\Http\Integrations\PokeApi\DataFactories;

use App\Http\Integrations\PokeApi\DataObjects\PokemonReference;
use App\Http\Integrations\PokeApi\Exceptions\InvalidPokeApiPayload;
use Illuminate\Support\Collection;

final readonly class PokemonReferenceFactory
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function make(array $attributes): PokemonReference
    {
        $name = $attributes['name'] ?? null;
        $url = $attributes['url'] ?? null;

        if (! is_string($name) || $name === '') {
            throw InvalidPokeApiPayload::missingOrInvalid('results.name');
        }

        if (! is_string($url) || $url === '') {
            throw InvalidPokeApiPayload::missingOrInvalid('results.url');
        }

        return new PokemonReference(name: $name, url: $url);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return Collection<int, PokemonReference>
     */
    public function collection(array $items): Collection
    {
        return collect($items)
            ->values()
            ->map(function (mixed $item, int $index): PokemonReference {
                return $this->make($this->stringKeyedArray($item, sprintf('results.%d', $index)));
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function stringKeyedArray(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            throw InvalidPokeApiPayload::missingOrInvalid($field);
        }

        $attributes = [];

        foreach ($value as $key => $item) {
            if (! is_string($key)) {
                throw InvalidPokeApiPayload::missingOrInvalid($field);
            }

            $attributes[$key] = $item;
        }

        return $attributes;
    }
}
