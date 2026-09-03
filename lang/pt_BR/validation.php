<?php

return [
    'accepted' => 'O campo :attribute deve ser aceito.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'between' => [
        'array' => 'O campo :attribute deve ter entre :min e :max itens.',
        'file' => 'O arquivo :attribute deve ter entre :min e :max quilobytes.',
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'email' => 'O campo :attribute deve ser um endereço de e-mail válido.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'json' => 'O campo :attribute deve ser um JSON válido.',
    'max' => [
        'array' => 'O campo :attribute não pode ter mais de :max itens.',
        'file' => 'O arquivo :attribute não pode ter mais de :max quilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'array' => 'O campo :attribute deve ter pelo menos :min itens.',
        'file' => 'O arquivo :attribute deve ter pelo menos :min quilobytes.',
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'O valor informado para :attribute já está em uso.',
    'url' => 'O campo :attribute deve ser uma URL válida.',

    'password' => [
        'letters' => 'O campo :attribute deve conter pelo menos uma letra.',
        'mixed' => 'O campo :attribute deve conter pelo menos uma letra maiúscula e uma minúscula.',
        'numbers' => 'O campo :attribute deve conter pelo menos um número.',
        'symbols' => 'O campo :attribute deve conter pelo menos um símbolo.',
        'uncompromised' => 'A senha informada apareceu em um vazamento de dados. Escolha outra senha.',
    ],

    'custom' => [],

    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'password_confirmation' => 'confirmação da senha',
        'metric' => 'métrica',
        'fields' => 'campos',
        'direction' => 'direção',
        'per_page' => 'itens por página',
        'page' => 'página',
    ],
];
