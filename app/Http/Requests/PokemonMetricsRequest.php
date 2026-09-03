<?php

namespace App\Http\Requests;

use App\Enums\PokemonMetric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PokemonMetricsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'metric' => $this->input('metric', PokemonMetric::Hp->value),
            'order' => $this->input('order', 'desc'),
            'fields' => $this->input('fields', 'name,value'),
            'per_page' => $this->input('per_page', 20),
        ]);
    }

    public function rules(): array
    {
        return [
            'metric' => ['sometimes', 'string', Rule::enum(PokemonMetric::class)],
            'order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'fields' => ['sometimes', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                $allowed = ['name', 'value'];

                foreach ($this->parseFields((string) $value) as $field) {
                    if (! in_array($field, $allowed, true)) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                }
            }],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function metric(): string
    {
        return (string) ($this->validated('metric') ?? PokemonMetric::Hp->value);
    }

    public function order(): string
    {
        return (string) ($this->validated('order') ?? 'desc');
    }

    /**
     * @return list<string>
     */
    public function fields(): array
    {
        return $this->parseFields((string) ($this->validated('fields') ?? 'name,value'));
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }

    /**
     * @return list<string>
     */
    private function parseFields(string $fields): array
    {
        return array_values(array_filter(array_map(trim(...), explode(',', $fields))));
    }
}
