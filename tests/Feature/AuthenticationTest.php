<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ash Ketchum',
            'email' => 'ash@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'ash@example.test')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
                'token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ash@example.test',
        ]);
    }

    public function test_user_can_login_and_access_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'ash@example.test',
            'password' => 'password',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'ash@example.test',
            'password' => 'password',
        ]);

        $login->assertOk()->assertJsonPath('data.id', $user->id);

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'ash@example.test');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'ash@example.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'ash@example.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout realizado com sucesso.');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        Auth::forgetGuards();
        $this->flushHeaders();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
