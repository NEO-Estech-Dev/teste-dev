<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'message',
            'token',
            'token_type',
        ]);

        $response->assertJson([
            'token_type' => 'Bearer Token',
        ]);
    }

    public function test_login_wrong_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            'message' => 'As credenciais informadas são inválidas.',
        ]);
    }

    public function test_login_without_credentials()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'email',
            'password',
        ]);
    }
}