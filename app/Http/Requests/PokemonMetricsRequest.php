<?php

namespace App\Http\Requests;

use App\Data\PokemonMetricsFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PokemonMetricsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'metric' => ['sometimes', 'string', Rule::in(PokemonMetricsFilters::metrics())],
            'field' => ['sometimes', 'string', Rule::in(PokemonMetricsFilters::fields())],
            'order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function filters(): PokemonMetricsFilters
    {
        return PokemonMetricsFilters::fromArray($this->validated());
    }
}
