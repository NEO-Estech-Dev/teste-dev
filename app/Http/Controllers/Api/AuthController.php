<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        // Se logou com sucesso, gera o token Sanctum
        $user = Auth::user();
        $token = $user->createToken('estech-avaliacao')->plainTextToken;

        return response()->json([
            'message' => 'Autenticado com sucesso!',
            'token' => $token,
            'type' => 'Bearer'
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoga apenas o token que o usuário usou para fazer esta requisição
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Deslogado com sucesso!'
        ]);
    }
}