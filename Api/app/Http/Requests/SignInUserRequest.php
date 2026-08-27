<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignInUserRequest extends FormRequest
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
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
            'password' => ['required', 'string','max:255']
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
            'string' => 'Deve conter apenas strings',
            'required' => 'Esse campo é obrigatório.',
            'email' => 'Email inválido.',
            'max' => 'Deve conter no máximo 255 caracteres',
            'exists' => 'Usuario ou senha inválidos.',
        ];
    }
}
