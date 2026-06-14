<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountLinkTest extends TestCase
{
    use RefreshDatabase;

    private function fakeVerify(array $override = []): void
    {
        Http::fake([
            '*/snapp/v1/verify-account' => Http::response(array_merge([
                'wp_user_id' => 42,
                'username' => 'mario.rossi',
                'email' => 'mario@sna.it',
                'roles' => ['subscriber'],
                'level' => 'premium',
                'level_label' => 'Premium',
            ], $override), 200),
            '*/snapp/v1/roles' => Http::response([
                ['key' => 'subscriber', 'name' => 'Sottoscrittore', 'users' => 1],
                ['key' => 'editor', 'name' => 'Editore', 'users' => 1],
                ['key' => 'presidente', 'name' => 'Presidente', 'users' => 1],
            ], 200),
        ]);
    }

    public function test_linking_creates_pivot_and_inherits_level(): void
    {
        $this->fakeVerify();
        $user = User::factory()->create(['membership_level' => null]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/account-links', ['identifier' => 'mario@sna.it', 'password' => 'secret'])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'verified')
            ->assertJsonPath('data.wp_account.level', 'premium');

        $this->assertDatabaseHas('wp_accounts', ['wp_user_id' => 42, 'level' => 'premium']);
        $this->assertDatabaseHas('account_links', ['user_id' => $user->id]);
        $this->assertSame('premium', $user->fresh()->membership_level);
    }

    public function test_unknown_account_returns_422(): void
    {
        Http::fake(['*/snapp/v1/verify-account' => Http::response(null, 404)]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/account-links', ['identifier' => 'ignoto@sna.it', 'password' => 'secret'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['identifier']]);
    }

    public function test_wrong_password_returns_422(): void
    {
        config(['snapp.wordpress.base_url' => 'https://snaservice.it']);
        Http::fake(['*/snapp/v1/verify-account' => Http::response(['code' => 'snapp_invalid_credentials'], 401)]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/account-links', ['identifier' => 'mario@sna.it', 'password' => 'sbagliata'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['password']])
            ->assertJsonPath('errors.password.0', 'Password errata. Controlla le credenziali del tuo account su snaservice.it.');

        $this->assertDatabaseCount('account_links', 0);
    }

    public function test_password_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/account-links', ['identifier' => 'mario@sna.it'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['password']]);
    }

    public function test_relinking_replaces_previous_link_one_per_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Due verifiche consecutive sullo stesso endpoint → sequenza di risposte.
        Http::fake([
            '*/snapp/v1/verify-account' => Http::sequence()
                ->push(['wp_user_id' => 42, 'username' => 'mario', 'email' => 'mario@sna.it', 'roles' => [], 'level' => 'iscritto', 'level_label' => 'Iscritto'], 200)
                ->push(['wp_user_id' => 99, 'username' => 'altro', 'email' => 'altro@sna.it', 'roles' => [], 'level' => 'premium', 'level_label' => 'Premium'], 200),
            '*/snapp/v1/roles' => Http::response([], 200),
        ]);

        $this->postJson('/api/v1/account-links', ['identifier' => 'mario@sna.it', 'password' => 'secret'])->assertStatus(201);
        $this->postJson('/api/v1/account-links', ['identifier' => 'altro@sna.it', 'password' => 'secret'])->assertStatus(201);

        // Un solo link per utente, livello aggiornato all'ultimo account.
        $this->assertSame(1, $user->accountLink()->count());
        $this->assertSame('premium', $user->fresh()->membership_level);
    }

    public function test_linking_stores_exact_wp_role_slug_and_name(): void
    {
        $this->fakeVerify(['roles' => ['presidente']]);
        $user = User::factory()->create(); // role app = member
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/account-links', ['identifier' => 'mario@sna.it', 'password' => 'secret'])->assertStatus(201);

        $fresh = $user->fresh();
        // Ruolo WP ESATTO (slug + nome dal sito).
        $this->assertSame('presidente', $fresh->wp_role);
        $this->assertSame('Presidente', $fresh->wp_role_label);
        // Il ruolo app (permessi) NON viene toccato.
        $this->assertSame('member', $fresh->role->value);
        $this->assertDatabaseHas('wp_accounts', ['wp_user_id' => 42, 'role' => 'presidente', 'role_label' => 'Presidente']);
    }

    public function test_linking_does_not_touch_app_permission_role(): void
    {
        $this->fakeVerify(['roles' => ['editor']]);
        $user = User::factory()->create(['role' => UserRole::Admin->value]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/account-links', ['identifier' => 'mario@sna.it', 'password' => 'secret'])->assertStatus(201);

        $fresh = $user->fresh();
        // Admin dell'app: il permesso resta admin; il ruolo WP è comunque registrato.
        $this->assertSame('admin', $fresh->role->value);
        $this->assertSame('editor', $fresh->wp_role);
        $this->assertSame('Editore', $fresh->wp_role_label);
    }

    public function test_exact_role_exposed_via_me(): void
    {
        $this->fakeVerify(['roles' => ['presidente']]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/account-links', ['identifier' => 'mario@sna.it', 'password' => 'secret'])->assertStatus(201);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.wp_role', 'presidente')
            ->assertJsonPath('data.wp_role_label', 'Presidente');
    }

    public function test_unlink_resets_level(): void
    {
        $this->fakeVerify();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/account-links', ['identifier' => 'mario@sna.it', 'password' => 'secret'])->assertStatus(201);
        $this->assertNotNull($user->fresh()->membership_level);

        $this->deleteJson('/api/v1/account-links')->assertOk();

        $this->assertNull($user->fresh()->membership_level);
        $this->assertNull($user->fresh()->wp_role);
        $this->assertSame(0, $user->accountLink()->count());
    }

    public function test_index_returns_current_link(): void
    {
        $this->fakeVerify();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/account-links', ['identifier' => 'mario@sna.it', 'password' => 'secret'])->assertStatus(201);

        $this->getJson('/api/v1/account-links')
            ->assertOk()
            ->assertJsonPath('data.wp_account.username', 'mario.rossi');
    }
}
