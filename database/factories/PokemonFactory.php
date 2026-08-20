<?php

namespace Database\Factories;

use App\Models\Pokemon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pokemon>
 */
class PokemonFactory extends Factory
{
    protected $model = Pokemon::class;

    public function definition(): array
    {
        return [
            'pokeapi_id' => fake()->unique()->numberBetween(1, 100000),
            'name' => fake()->unique()->slug(1),
            'height' => fake()->numberBetween(1, 80),
            'weight' => fake()->numberBetween(1, 2000),
            'hp' => fake()->numberBetween(1, 255),
            'attack' => fake()->numberBetween(1, 255),
            'defense' => fake()->numberBetween(1, 255),
            'special_attack' => fake()->numberBetween(1, 255),
            'special_defense' => fake()->numberBetween(1, 255),
            'speed' => fake()->numberBetween(1, 255),
            'sprite_url' => 'https://example.test/sprite.png',
        ];
    }
}
