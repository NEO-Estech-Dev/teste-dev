<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PokemonField;
use App\Enums\PokemonMetric;
use App\Enums\RankingOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PokemonRankingRequest extends FormRequest
{
    private const string DEFAULT_METRIC = 'hp';

    private const string DEFAULT_FIELD = 'name';

    private const string DEFAULT_ORDER = 'desc';

    private const int DEFAULT_PAGE = 1;

    private const int DEFAULT_PER_PAGE = 10;

    private bool $metricWasProvided = false;

    private bool $fieldWasProvided = false;

    private bool $orderWasProvided = false;

    private bool $perPageWasProvided = false;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            /**
             * Métrica usada para ordenar o ranking.
             *
             * @default hp
             *
             * @example hp
             */
            'metric' => ['sometimes', 'string', Rule::in(PokemonMetric::values())],
            /**
             * Campo retornado para cada Pokémon.
             *
             * @default name
             *
             * @example name
             */
            'field' => ['sometimes', 'string', Rule::in(PokemonField::values())],
            /**
             * Direção da ordenação.
             *
             * @default desc
             *
             * @example desc
             */
            'order' => ['sometimes', 'string', Rule::in(RankingOrder::values())],
            /**
             * Página solicitada.
             *
             * @default 1
             *
             * @example 1
             */
            'page' => ['sometimes', 'integer', 'min:1'],
            /**
             * Quantidade de itens por página.
             *
             * @default 10
             *
             * @example 10
             */
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    public function metric(): string
    {
        return $this->string('metric')->toString();
    }

    public function field(): string
    {
        return $this->string('field')->toString();
    }

    public function order(): string
    {
        return $this->string('order')->toString();
    }

    public function page(): int
    {
        return $this->integer('page');
    }

    public function perPage(): int
    {
        return $this->integer('per_page');
    }

    /**
     * @return array<string, int|string>
     */
    public function paginationParameters(): array
    {
        $parameters = [];

        if ($this->metricWasProvided) {
            $parameters['metric'] = $this->metric();
        }

        if ($this->fieldWasProvided) {
            $parameters['field'] = $this->field();
        }

        if ($this->orderWasProvided) {
            $parameters['order'] = $this->order();
        }

        if ($this->perPageWasProvided) {
            $parameters['per_page'] = $this->perPage();
        }

        return $parameters;
    }

    protected function prepareForValidation(): void
    {
        $this->metricWasProvided = $this->query->has('metric');
        $this->fieldWasProvided = $this->query->has('field');
        $this->orderWasProvided = $this->query->has('order');
        $this->perPageWasProvided = $this->query->has('per_page');

        $this->mergeIfMissing([
            'metric' => self::DEFAULT_METRIC,
            'field' => self::DEFAULT_FIELD,
            'order' => self::DEFAULT_ORDER,
            'page' => self::DEFAULT_PAGE,
            'per_page' => self::DEFAULT_PER_PAGE,
        ]);
    }
}
