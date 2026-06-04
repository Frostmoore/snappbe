<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\QueuedResetPassword as ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'reset@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_does_not_leak_unknown_email(): void
    {
        // Risposta positiva anche se l'email non esiste (anti user-enumeration).
        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'ignoto@example.com'])->assertOk();
    }

    public function test_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/password/reset', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'NuovaPass123!',
            'password_confirmation' => 'NuovaPass123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NuovaPass123!', $user->fresh()->password));
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Vecchia123!')]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/password/change', [
            'current_password' => 'Vecchia123!',
            'password' => 'Nuova123!',
            'password_confirmation' => 'Nuova123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('Nuova123!', $user->fresh()->password));
    }

    public function test_change_password_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Vecchia123!')]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/password/change', [
            'current_password' => 'sbagliata',
            'password' => 'Nuova123!',
            'password_confirmation' => 'Nuova123!',
        ])->assertStatus(422);
    }
}
