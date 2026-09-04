<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    /**
     * Issue a personal access token for the given credentials.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
            // Same message for unknown e-mail and wrong password: the response
            // must not reveal which accounts exist.
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $user->createToken($request->string('device_name', 'api')->value());

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Token revoked.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }
}
