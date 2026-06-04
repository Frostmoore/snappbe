<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_token(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ])->assertOk()->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'sbagliata',
        ])->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['email']]);
    }
}
