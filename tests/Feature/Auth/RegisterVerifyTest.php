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

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($url)->assertStatus(302); // redirect al deep-link app

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
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
