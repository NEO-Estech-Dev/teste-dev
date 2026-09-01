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
            'pokeapi_id'      => fake()->unique()->randomNumber(4),
            'name'            => fake()->unique()->word(),
            'hp'              => fake()->numberBetween(10, 255),
            'attack'          => fake()->numberBetween(10, 255),
            'defense'         => fake()->numberBetween(10, 255),
            'special_attack'  => fake()->numberBetween(10, 255),
            'special_defense' => fake()->numberBetween(10, 255),
            'speed'           => fake()->numberBetween(10, 255),
            'weight'          => fake()->numberBetween(10, 1000),
            'height'          => fake()->numberBetween(1, 100),
        ];
    }
}
