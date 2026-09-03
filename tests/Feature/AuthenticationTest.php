<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ash Ketchum',
            'email' => 'ash@example.com',
            'password' => 'pikachu123',
            'password_confirmation' => 'pikachu123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'ash@example.com')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', ['email' => 'ash@example.com']);
    }

    public function test_user_can_login_and_revoke_the_current_token(): void
    {
        User::factory()->create([
            'email' => 'misty@example.com',
            'password' => 'starmie123',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'misty@example.com',
            'password' => 'starmie123',
        ])->assertOk();

        $token = $login->json('token');

        $this->withToken($token)
            ->deleteJson('/api/v1/auth/token')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'brock@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'brock@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_validation_messages_are_returned_in_portuguese(): void
    {
        $this->postJson('/api/v1/auth/register')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'O campo nome é obrigatório. (e mais 2 erros)')
            ->assertJsonPath('errors.name.0', 'O campo nome é obrigatório.')
            ->assertJsonPath('errors.email.0', 'O campo e-mail é obrigatório.')
            ->assertJsonPath('errors.password.0', 'O campo senha é obrigatório.');
    }
}
