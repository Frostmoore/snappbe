<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    private function mockSocialite(string $id, ?string $email, string $name = 'Mario Rossi'): void
    {
        // Percorso reale: attivo solo con credenziali configurate.
        config(['snapp.oauth.google.client_id' => 'test-client-id']);

        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn($id);
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getName')->andReturn($name);
        $socialUser->shouldReceive('getNickname')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('userFromToken')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_social_login_creates_new_user_and_returns_token(): void
    {
        $this->mockSocialite('google-123', 'social@example.com');

        $this->postJson('/api/v1/auth/social/google', ['token' => 'fake-id-token'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']])
            ->assertJsonPath('data.user.email', 'social@example.com')
            ->assertJsonPath('data.user.email_verified', true);

        $this->assertDatabaseHas('users', [
            'email' => 'social@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
    }

    public function test_social_login_links_existing_email_account(): void
    {
        $existing = User::factory()->create(['email' => 'social@example.com', 'provider' => null]);
        $this->mockSocialite('google-123', 'social@example.com');

        $this->postJson('/api/v1/auth/social/google', ['token' => 'fake-id-token'])->assertOk();

        $this->assertEquals('google', $existing->fresh()->provider);
        $this->assertSame(1, User::whereEmail('social@example.com')->count());
    }

    public function test_unsupported_provider_returns_404(): void
    {
        $this->postJson('/api/v1/auth/social/facebook', ['token' => 'x'])->assertStatus(404);
    }

    public function test_invalid_token_returns_401(): void
    {
        config(['snapp.oauth.google.client_id' => 'test-client-id']);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('userFromToken')->andThrow(new \RuntimeException('invalid'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->postJson('/api/v1/auth/social/google', ['token' => 'bad'])->assertStatus(401);
    }

    public function test_mock_social_login_creates_user_when_enabled(): void
    {
        // Nessuna credenziale reale + mock attivo (dev).
        config(['snapp.oauth.google.client_id' => '', 'snapp.oauth.mock' => true]);

        $this->postJson('/api/v1/auth/social/google', ['token' => 'mock'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']])
            ->assertJsonPath('data.user.email', 'google.mock@sna.it')
            ->assertJsonPath('data.user.email_verified', true);

        $this->assertDatabaseHas('users', [
            'email' => 'google.mock@sna.it',
            'provider' => 'google',
            'provider_id' => 'mock-google',
        ]);
    }

    public function test_social_login_not_configured_returns_501(): void
    {
        // Né credenziali reali né mock → non configurato.
        config(['snapp.oauth.google.client_id' => '', 'snapp.oauth.mock' => false]);

        $this->postJson('/api/v1/auth/social/google', ['token' => 'mock'])->assertStatus(501);
    }
}
