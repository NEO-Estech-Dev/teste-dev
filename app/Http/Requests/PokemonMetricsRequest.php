<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MetricOrder;
use App\Enums\PokemonField;
use App\Enums\PokemonMetric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the metrics endpoint. Every parameter is optional; anything not
 * supplied falls back to the default declared on the enums.
 */
final class PokemonMetricsRequest extends FormRequest
{
    private const MAX_LIMIT = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept `fields=name,value` as well as `fields[]=name&fields[]=value`.
     */
    protected function prepareForValidation(): void
    {
        $fields = $this->input('fields');
        $onlyDefault = $this->input('only_default');

        if (is_string($fields)) {
            $this->merge([
                'fields' => array_values(array_filter(array_map('trim', explode(',', $fields)))),
            ]);
        }

        if (is_string($onlyDefault)) {
            $normalizedOnlyDefault = match (strtolower(trim($onlyDefault))) {
                'true' => true,
                'false' => false,
                default => $onlyDefault,
            };

            $this->merge(['only_default' => $normalizedOnlyDefault]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'metric' => ['sometimes', 'string', Rule::in(PokemonMetric::accepted())],
            'order' => ['sometimes', 'string', Rule::in(MetricOrder::accepted())],
            'fields' => ['sometimes', 'array'],
            'fields.*' => ['string', Rule::in(PokemonField::accepted())],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
            'page' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', 'string', 'max:32'],
            'only_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'metric.in' => 'Unsupported metric. Accepted values: '.implode(', ', PokemonMetric::accepted()).'.',
            'order.in' => 'Unsupported order. Accepted values: '.implode(', ', MetricOrder::accepted()).'.',
            'fields.*.in' => 'Unsupported field. Accepted values: '.implode(', ', PokemonField::accepted()).'.',
        ];
    }

    public function metric(): PokemonMetric
    {
        return PokemonMetric::tryFromInput($this->query('metric')) ?? PokemonMetric::default();
    }

    public function order(): MetricOrder
    {
        return MetricOrder::tryFromInput($this->query('order')) ?? MetricOrder::default();
    }

    /**
     * @return list<PokemonField>
     */
    public function fields(): array
    {
        $fields = $this->input('fields');

        return is_array($fields) && $fields !== []
            ? PokemonField::fromList($fields)
            : PokemonField::defaults();
    }

    public function perPage(): int
    {
        return min((int) $this->input('limit', 10), self::MAX_LIMIT);
    }

    public function page(): int
    {
        return max((int) $this->input('page', 1), 1);
    }

    public function typeFilter(): ?string
    {
        $type = $this->input('type');

        return is_string($type) && $type !== '' ? strtolower(trim($type)) : null;
    }

    public function onlyDefault(): bool
    {
        return $this->boolean('only_default');
    }
}
