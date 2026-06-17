<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\SocialTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sostituisce il verificatore token con un mock: il provider è "configurato"
     * e restituisce l'identità indicata ($identity = null → token non valido).
     *
     * @param  array{id:string,email:?string,name:?string}|null  $identity
     */
    private function fakeVerifier(string $provider, ?array $identity): void
    {
        config(["snapp.oauth.{$provider}.client_id" => 'test-client-id']);

        $mock = Mockery::mock(SocialTokenVerifier::class);
        $mock->shouldReceive($provider)->andReturn($identity);
        $this->app->instance(SocialTokenVerifier::class, $mock);
    }

    public function test_social_login_creates_new_user_and_returns_token(): void
    {
        $this->fakeVerifier('google', ['id' => 'google-123', 'email' => 'social@example.com', 'name' => 'Mario Rossi']);

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
        $this->fakeVerifier('google', ['id' => 'google-123', 'email' => 'social@example.com', 'name' => 'Mario Rossi']);

        $this->postJson('/api/v1/auth/social/google', ['token' => 'fake-id-token'])->assertOk();

        $this->assertEquals('google', $existing->fresh()->provider);
        $this->assertSame(1, User::whereEmail('social@example.com')->count());
    }

    public function test_apple_login_creates_user(): void
    {
        $this->fakeVerifier('apple', ['id' => 'apple-999', 'email' => 'mela@example.com', 'name' => null]);

        $this->postJson('/api/v1/auth/social/apple', ['token' => 'fake-apple-jwt'])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'mela@example.com');

        $this->assertDatabaseHas('users', [
            'provider' => 'apple',
            'provider_id' => 'apple-999',
        ]);
    }

    public function test_apple_login_uses_name_from_request_when_token_has_none(): void
    {
        // Apple non mette il nome nel token: l'app lo invia a parte (primo consenso).
        $this->fakeVerifier('apple', ['id' => 'apple-name-1', 'email' => 'n@example.com', 'name' => null]);

        $this->postJson('/api/v1/auth/social/apple', ['token' => 'jwt', 'name' => 'Giulia Verdi'])->assertOk();

        $this->assertDatabaseHas('users', ['provider_id' => 'apple-name-1', 'name' => 'Giulia Verdi']);
    }

    public function test_unsupported_provider_returns_404(): void
    {
        $this->postJson('/api/v1/auth/social/facebook', ['token' => 'x'])->assertStatus(404);
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->fakeVerifier('google', null);

        $this->postJson('/api/v1/auth/social/google', ['token' => 'bad'])->assertStatus(401);
    }

    public function test_invalid_apple_token_returns_401(): void
    {
        $this->fakeVerifier('apple', null);

        $this->postJson('/api/v1/auth/social/apple', ['token' => 'bad'])->assertStatus(401);
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
