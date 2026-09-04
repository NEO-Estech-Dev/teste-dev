<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Ranking direction. "best" and "worst" are accepted because that is how the
 * requirement is phrased (maiores/melhores, menores/piores).
 */
enum MetricOrder: string
{
    case Desc = 'desc';
    case Asc = 'asc';

    /** @var array<string, string> */
    private const ALIASES = [
        'best' => 'desc',
        'worst' => 'asc',
    ];

    public static function default(): self
    {
        return self::Desc;
    }

    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        return self::ALIASES[$value] ?? $value;
    }

    public static function tryFromInput(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom(self::normalize($value));
    }

    /**
     * @return list<string>
     */
    public static function accepted(): array
    {
        return array_values(array_unique([
            ...array_column(self::cases(), 'value'),
            ...array_keys(self::ALIASES),
        ]));
    }

    public function direction(): string
    {
        return $this->value;
    }
}
