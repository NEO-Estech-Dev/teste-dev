<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pokemon;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pokemon>
 */
class PokemonFactory extends Factory
{
    protected $model = Pokemon::class;

    /**
     * The canonical six stats, used when a test does not care about the values.
     */
    private const DEFAULT_STATS = [
        'hp' => 50,
        'attack' => 50,
        'defense' => 50,
        'special-attack' => 50,
        'special-defense' => 50,
        'speed' => 50,
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->numberBetween(1, 100_000),
            'name' => $this->faker->unique()->lexify('pokemon-????????'),
            'height' => $this->faker->numberBetween(1, 200),
            'weight' => $this->faker->numberBetween(1, 10_000),
            'base_experience' => $this->faker->numberBetween(30, 400),
            'order' => $this->faker->numberBetween(1, 1_000),
            'is_default' => true,
            'sprite_url' => $this->faker->url(),
            'stats_total' => array_sum(self::DEFAULT_STATS),
        ];
    }

    /**
     * Attach base stats and keep `stats_total` consistent with them.
     *
     * @param  array<string, int>  $stats
     */
    public function withStats(array $stats = []): static
    {
        $stats = [...self::DEFAULT_STATS, ...$stats];

        return $this->afterCreating(function (Pokemon $pokemon) use ($stats): void {
            foreach ($stats as $stat => $value) {
                $pokemon->stats()->create([
                    'stat' => $stat,
                    'base_stat' => $value,
                    'effort' => 0,
                ]);
            }

            $pokemon->forceFill(['stats_total' => array_sum($stats)])->save();
        });
    }

    /**
     * @param  list<string>  $names
     */
    public function withTypes(array $names): static
    {
        return $this->afterCreating(function (Pokemon $pokemon) use ($names): void {
            $slot = 1;

            foreach ($names as $name) {
                $type = Type::query()->firstOrCreate(['name' => $name]);
                $pokemon->types()->attach($type->id, ['slot' => $slot++]);
            }
        });
    }

    public function alternateForm(): static
    {
        return $this->state(fn (): array => ['is_default' => false]);
    }
}
