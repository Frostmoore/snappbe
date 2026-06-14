<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SnaLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeVerify(array $override = [], int $status = 200): void
    {
        Http::fake([
            '*/snapp/v1/verify-account' => Http::response(array_merge([
                'wp_user_id'  => 77,
                'username'    => 'mario.rossi',
                'email'       => 'mario@sna.it',
                'roles'       => ['subscriber'],
                'level'       => 'premium',
                'level_label' => 'Premium',
            ], $override), $status),
            '*/snapp/v1/roles' => Http::response([
                ['key' => 'subscriber', 'name' => 'Sottoscrittore', 'users' => 1],
            ], 200),
        ]);
    }

    public function test_sna_login_creates_user_links_account_and_returns_token(): void
    {
        $this->fakeVerify();

        $this->postJson('/api/v1/auth/sna', ['identifier' => 'mario@sna.it', 'password' => 'wp-secret'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']])
            ->assertJsonPath('data.user.email', 'mario@sna.it')
            ->assertJsonPath('data.user.email_verified', true)
            ->assertJsonPath('data.user.membership_level', 'premium');

        $this->assertDatabaseHas('users', ['email' => 'mario@sna.it']);
        $this->assertDatabaseHas('wp_accounts', ['wp_user_id' => 77, 'level' => 'premium']);
        $user = User::whereEmail('mario@sna.it')->first();
        $this->assertDatabaseHas('account_links', ['user_id' => $user->id]);
    }

    public function test_sna_login_links_existing_app_user_without_duplicating(): void
    {
        User::factory()->create(['email' => 'mario@sna.it']);
        $this->fakeVerify();

        $this->postJson('/api/v1/auth/sna', ['identifier' => 'mario@sna.it', 'password' => 'wp-secret'])->assertOk();

        $this->assertSame(1, User::whereEmail('mario@sna.it')->count());
    }

    public function test_sna_login_sets_app_password_for_email_login(): void
    {
        $this->fakeVerify();

        // Crea l'account via SNA con una password specifica.
        $this->postJson('/api/v1/auth/sna', ['identifier' => 'mario@sna.it', 'password' => 'wp-secret-123'])->assertOk();

        // Ora deve poter accedere anche con email+password (stessa password SNA).
        $this->postJson('/api/v1/auth/login', ['email' => 'mario@sna.it', 'password' => 'wp-secret-123'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_sna_login_wrong_password_returns_422(): void
    {
        config(['snapp.wordpress.base_url' => 'https://snaservice.it']);
        $this->fakeVerify(status: 401);

        $this->postJson('/api/v1/auth/sna', ['identifier' => 'mario@sna.it', 'password' => 'sbagliata'])
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password errata. Controlla le credenziali del tuo account su snaservice.it.');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_sna_login_unknown_account_returns_422(): void
    {
        Http::fake(['*/snapp/v1/verify-account' => Http::response(null, 404)]);

        $this->postJson('/api/v1/auth/sna', ['identifier' => 'ignoto@sna.it', 'password' => 'x'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['identifier']]);
    }
}
