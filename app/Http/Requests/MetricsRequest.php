<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MetricsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'metric' => ['sometimes', 'string', Rule::in(['hp', 'attack', 'defense', 'special_attack', 'special_defense', 'speed'])],
            'fields' => [
                'sometimes',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $allowed = ['pokeapi_id', 'name', 'height', 'weight', 'base_experience', 'types', 'metric'];
                    $fields = array_filter(array_map('trim', explode(',', (string) $value)));

                    if ($fields === [] || array_diff($fields, $allowed) !== []) {
                        $fail('O campo campos contém valores inválidos. Permitidos: '.implode(', ', $allowed).'.');
                    }
                },
            ],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
