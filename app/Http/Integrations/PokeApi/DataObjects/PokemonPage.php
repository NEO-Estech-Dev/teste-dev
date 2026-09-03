<?php

declare(strict_types=1);

namespace App\Http\Integrations\PokeApi\DataObjects;

use Illuminate\Support\Collection;

final readonly class PokemonPage
{
    /**
     * @param  Collection<int, PokemonReference>  $items
     */
    public function __construct(
        public Collection $items,
        public bool $hasNextPage,
        public int $total,
    ) {}

    /**
     * @return array{items: list<array{name: string, url: string}>, hasNextPage: bool, total: int}
     */
    public function toArray(): array
    {
        return [
            'items' => array_values($this->items
                ->map(static fn (PokemonReference $pokemon): array => $pokemon->toArray())
                ->values()
                ->all()),
            'hasNextPage' => $this->hasNextPage,
            'total' => $this->total,
        ];
    }
}
