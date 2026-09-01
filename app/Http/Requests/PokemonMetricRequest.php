<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PokemonMetricRequest extends FormRequest
{
    /**
     * Autoriza qualquer usuário a fazer a requisição (pois é uma API pública)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para os parâmetros da URL
     */
    public function rules(): array
    {
        return [
            // Garante que a métrica seja apenas uma das que existem no banco
            'metric' => ['sometimes', 'string', 'in:hp,attack,defense,special_attack,special_defense,speed'],
            
            // Campos específicos permitidos para retorno
            'field'  => ['sometimes', 'string', 'in:name,weight,height'],
            
            // Ordenação restrita a ascendente ou descendente
            'sort'   => ['sometimes', 'string', 'in:asc,desc'],
            
            // Paginação segura (evita que peçam 1 milhão de registros de uma vez)
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
