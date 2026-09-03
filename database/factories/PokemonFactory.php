<?php

namespace Database\Factories;

use App\Models\Pokemon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pokemon>
 */
class PokemonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pokeapi_id' => fake()->unique()->numberBetween(1, 100_000),
            'name' => fake()->unique()->slug(2),
            'height' => fake()->numberBetween(1, 200),
            'weight' => fake()->numberBetween(1, 10_000),
            'base_experience' => fake()->numberBetween(30, 400),
            'types' => [fake()->randomElement(['fire', 'water', 'grass', 'normal'])],
            'hp' => fake()->numberBetween(1, 255),
            'attack' => fake()->numberBetween(1, 255),
            'defense' => fake()->numberBetween(1, 255),
            'special_attack' => fake()->numberBetween(1, 255),
            'special_defense' => fake()->numberBetween(1, 255),
            'speed' => fake()->numberBetween(1, 255),
        ];
    }
}
