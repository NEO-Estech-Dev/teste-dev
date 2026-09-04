<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_issues_a_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'demo@estech.test',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@estech.test',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'token_type']);

        $this->withHeader('Authorization', 'Bearer '.$response->json('token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'demo@estech.test');
    }

    #[Test]
    public function it_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'demo@estech.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@estech.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    #[Test]
    public function a_token_can_be_revoked(): void
    {
        $user = User::factory()->create([
            'email' => 'demo@estech.test',
            'password' => 'password',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        // Guards keep the user they resolved for the lifetime of the
        // application instance; without this the next request would be
        // answered from that cache instead of re-checking the token.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }
}
