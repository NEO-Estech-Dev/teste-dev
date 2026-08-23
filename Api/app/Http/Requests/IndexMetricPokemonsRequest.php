<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMetricPokemonsRequest extends FormRequest
{
    private const ALLOWED_FIELDS = [
        'id', 'pokemon_id', 'name', 'height', 'weight', 'order', 'specie', 'base_experience'
    ];

    public function rules(): array
    {
        return [
            'page'   => ['required', 'integer', 'min:1'],
            'limit'  => ['required', 'integer', 'min:1', 'max:200'],
            'metric' => ['nullable', Rule::in(['height','weight','order','specie','base_experience'])],
            'fields' => ['nullable', 'string', function ($attribute, $value, $fail) {
                $invalid = array_diff(explode(',', $value), self::ALLOWED_FIELDS);
                if ($invalid) {
                    $fail('Campos permitidos:id, pokemon_id, name, height, weight, order, specie, base_experience');
                }
            }],
            'order'  => ['nullable','in:asc,desc'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório',
            'integer' => 'Deve conter apenas inteiros.',
            'min' => 'Deve conter um valor minimo de 1',
            'limit.min' => 'Limite mínimo para paginação é 1.',
            'limit.max' => 'Limite máximo para paginação é 200.',
            'metric.in' => 'Metricas permitidas: height, weight, order, specie e base_experience',
            'string' => 'Deve conter apenas strings'
        ];
    }
}