<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'me@example.com');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertSame(0, $user->fresh()->tokens()->count());
    }
}
