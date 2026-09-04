<?php

declare(strict_types=1);

namespace App\Enums;

enum RankingOrder: string
{
    case Ascending = 'asc';
    case Descending = 'desc';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $order): string => $order->value,
            self::cases(),
        );
    }

    public function direction(): string
    {
        return match ($this) {
            self::Ascending => 'asc',
            self::Descending => 'desc',
        };
    }
}
