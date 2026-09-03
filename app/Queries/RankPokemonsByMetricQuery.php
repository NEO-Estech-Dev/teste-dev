<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\PokemonField;
use App\Enums\PokemonMetric;
use App\Enums\RankingOrder;
use App\Models\Pokemon;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

final readonly class RankPokemonsByMetricQuery
{
    private const string INVALID_METRIC = 'Unsupported Pokemon metric.';

    private const string INVALID_FIELD = 'Unsupported Pokemon field.';

    private const string INVALID_ORDER = 'Unsupported ranking order.';

    private const string INVALID_PAGE = 'The ranking page must be at least one.';

    private const string INVALID_PAGE_SIZE = 'The ranking page size must be between one and one hundred.';

    /**
     * @return LengthAwarePaginator<int, Pokemon>
     */
    public function handle(
        string $metric,
        string $field,
        string $order,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $metricColumn = PokemonMetric::tryFrom($metric)?->column()
            ?? throw new InvalidArgumentException(self::INVALID_METRIC);
        $fieldColumn = PokemonField::tryFrom($field)?->column()
            ?? throw new InvalidArgumentException(self::INVALID_FIELD);
        $orderDirection = RankingOrder::tryFrom($order)?->direction()
            ?? throw new InvalidArgumentException(self::INVALID_ORDER);

        if ($page < 1) {
            throw new InvalidArgumentException(self::INVALID_PAGE);
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException(self::INVALID_PAGE_SIZE);
        }

        return Pokemon::query()
            ->when(
                $metricColumn === PokemonMetric::BaseExperience->column(),
                static fn ($query) => $query->whereNotNull($metricColumn),
            )
            ->orderBy($metricColumn, $orderDirection)
            ->orderBy(PokemonField::PokeapiId->column(), $orderDirection)
            ->paginate(
                perPage: $perPage,
                columns: [$fieldColumn],
                pageName: 'page',
                page: $page,
            );
    }
}
