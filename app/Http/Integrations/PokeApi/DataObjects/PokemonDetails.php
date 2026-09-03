<?php

declare(strict_types=1);

namespace App\Http\Integrations\PokeApi\DataObjects;

final readonly class PokemonDetails
{
    public function __construct(
        public int $pokeapiId,
        public string $name,
        public int $height,
        public int $weight,
        public ?int $baseExperience,
        public int $hp,
        public int $attack,
        public int $defense,
        public int $specialAttack,
        public int $specialDefense,
        public int $speed,
    ) {}

    /**
     * @return array{pokeapiId: int, name: string, height: int, weight: int, baseExperience: int|null, hp: int, attack: int, defense: int, specialAttack: int, specialDefense: int, speed: int}
     */
    public function toArray(): array
    {
        return [
            'pokeapiId' => $this->pokeapiId,
            'name' => $this->name,
            'height' => $this->height,
            'weight' => $this->weight,
            'baseExperience' => $this->baseExperience,
            'hp' => $this->hp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'specialAttack' => $this->specialAttack,
            'specialDefense' => $this->specialDefense,
            'speed' => $this->speed,
        ];
    }
}
