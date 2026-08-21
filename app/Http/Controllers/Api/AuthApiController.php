<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $this->validateFieldsWereFilled($request);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'As credenciais informadas são inválidas.',
            ], 401);
        }

        $user = Auth::user();

        $token = $user->createToken('api-token', ['*'], now()->addMinutes(5))->plainTextToken;

        return response()->json([
            'message' => 'Autenticação realizada com sucesso.',
            'token' => $token,
            'token_type' => 'Bearer Token',
        ]);
    }

    public function validateFieldsWereFilled(Request $request)
    {
        return $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ],
        [
            'email.required' => 'O campo de e-mail é obrigatório!',
            'email.password' => 'O campo de senha é obrigatório!',
        ]
        );
    }
}
