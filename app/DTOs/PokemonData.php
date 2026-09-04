<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Normalized shape of a single /pokemon/{id} payload.
 *
 * Transformation happens here, once, so the ingest service only ever deals
 * with rows that are ready to be written.
 */
final readonly class PokemonData
{
    /**
     * @param  list<array{stat: string, base_stat: int, effort: int}>  $stats
     * @param  list<array{name: string, slot: int}>  $types
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $height,
        public int $weight,
        public ?int $baseExperience,
        public int $order,
        public bool $isDefault,
        public ?string $spriteUrl,
        public int $statsTotal,
        public array $stats,
        public array $types,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromApi(array $payload): self
    {
        $stats = [];
        $statsTotal = 0;

        foreach ($payload['stats'] ?? [] as $stat) {
            $baseStat = (int) ($stat['base_stat'] ?? 0);
            $statsTotal += $baseStat;

            $stats[] = [
                'stat' => (string) ($stat['stat']['name'] ?? ''),
                'base_stat' => $baseStat,
                'effort' => (int) ($stat['effort'] ?? 0),
            ];
        }

        $types = [];

        foreach ($payload['types'] ?? [] as $type) {
            $types[] = [
                'name' => (string) ($type['type']['name'] ?? ''),
                'slot' => (int) ($type['slot'] ?? 1),
            ];
        }

        return new self(
            id: (int) $payload['id'],
            name: (string) $payload['name'],
            height: (int) ($payload['height'] ?? 0),
            weight: (int) ($payload['weight'] ?? 0),
            baseExperience: isset($payload['base_experience'])
                ? (int) $payload['base_experience']
                : null,
            order: (int) ($payload['order'] ?? 0),
            isDefault: (bool) ($payload['is_default'] ?? true),
            spriteUrl: self::spriteFrom($payload),
            statsTotal: $statsTotal,
            stats: $stats,
            types: $types,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'height' => $this->height,
            'weight' => $this->weight,
            'base_experience' => $this->baseExperience,
            'order' => $this->order,
            'is_default' => $this->isDefault,
            'sprite_url' => $this->spriteUrl,
            'stats_total' => $this->statsTotal,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function statRows(): array
    {
        return array_map(fn (array $stat): array => [
            'pokemon_id' => $this->id,
            'stat' => $stat['stat'],
            'base_stat' => $stat['base_stat'],
            'effort' => $stat['effort'],
        ], $this->stats);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function spriteFrom(array $payload): ?string
    {
        $sprite = $payload['sprites']['other']['official-artwork']['front_default']
            ?? $payload['sprites']['front_default']
            ?? null;

        return is_string($sprite) ? $sprite : null;
    }
}
