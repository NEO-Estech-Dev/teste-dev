<?php

declare(strict_types=1);

namespace App\Http\Integrations\PokeApi\DataObjects;

final readonly class PokemonReference
{
    public function __construct(
        public string $name,
        public string $url,
    ) {}

    /**
     * @return array{name: string, url: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
        ];
    }
}
