<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\QueuedVerifyEmail as VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegisterVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_unverified_user_and_returns_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'role', 'email_verified']]])
            ->assertJsonPath('data.user.email_verified', false)
            ->assertJsonPath('data.user.role', 'member');

        $this->assertDatabaseHas('users', ['email' => 'mario@example.com']);
        Notification::assertSentTo(User::whereEmail('mario@example.com')->first(), VerifyEmail::class);
    }

    public function test_register_rejects_weak_password(): void
    {
        // Troppo corta, senza maiuscole/numeri/simboli → deve fallire.
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Mario',
            'email' => 'weak@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['password']]);

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_register_requires_matching_confirmation(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Mario',
            'email' => 'mismatch@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Altra123!',
        ])->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['password']]);

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.com']);
    }

    public function test_password_is_hashed_not_plaintext(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Mario',
            'email' => 'hash@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(201);

        $user = User::whereEmail('hash@example.com')->first();
        $this->assertNotSame('Password123!', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Password123!', $user->password));
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'X',
            'email' => 'dup@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['email']]);
    }

    public function test_signed_verification_link_verifies_email(): void
    {
        $user = User::factory()->unverified()->create();

        // Firma RELATIVA: come la genera AppServiceProvider in produzione.
        $path = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ], absolute: false);

        $this->get($path)
            ->assertStatus(200)
            ->assertSee('Email verificata', false);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verified_middleware_blocks_unverified_account(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create());

        // L'account NON è attivo finché l'email non è verificata: 403 sulle funzioni.
        $this->getJson('/api/v1/account-links')
            ->assertStatus(403)
            ->assertJsonPath('errors.email.0', 'email_unverified');
    }

    public function test_verified_account_passes_middleware(): void
    {
        Sanctum::actingAs(User::factory()->create()); // factory = verificato

        $this->getJson('/api/v1/account-links')->assertOk();
    }

    public function test_me_and_resend_reachable_while_unverified(): void
    {
        Notification::fake();
        Sanctum::actingAs(User::factory()->unverified()->create());

        // /me e resend devono restare accessibili: servono a gestire la verifica.
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email_verified', false);
        $this->postJson('/api/v1/auth/verify/resend')->assertOk();
    }

    public function test_resend_verification_for_authenticated_user(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/verify/resend')->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
