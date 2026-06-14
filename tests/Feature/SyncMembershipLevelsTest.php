<?php

namespace Tests\Feature;

use App\Enums\AccountLinkStatus;
use App\Enums\UserRole;
use App\Models\AccountLink;
use App\Models\User;
use App\Models\WpAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncMembershipLevelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_realigns_level_and_role_via_account_info(): void
    {
        $user = User::factory()->create(['membership_level' => 'iscritto']); // role member
        $wp = WpAccount::create([
            'wp_user_id'  => 77,
            'username'    => 'mario',
            'email'       => 'mario@sna.it',
            'level'       => 'iscritto',
            'level_label' => 'Iscritto',
            'roles'       => ['subscriber'],
            'synced_at'   => now(),
        ]);
        AccountLink::create([
            'user_id'             => $user->id,
            'wp_account_id'       => $wp->id,
            'status'              => AccountLinkStatus::Verified->value,
            'verification_method' => 'wp_bridge',
            'linked_at'           => now(),
        ]);

        // WP ora restituisce un ruolo presidente + livello premium (senza password, solo HMAC).
        Http::fake([
            '*/snapp/v1/account-info' => Http::response([
                'wp_user_id'  => 77,
                'username'    => 'mario',
                'email'       => 'mario@sna.it',
                'roles'       => ['presidente'],
                'level'       => 'premium',
                'level_label' => 'Premium',
            ], 200),
            '*/snapp/v1/roles' => Http::response([
                ['key' => 'presidente', 'name' => 'Presidente', 'users' => 1],
            ], 200),
        ]);

        $this->artisan('snapp:sync-levels')->assertSuccessful();

        $fresh = $user->fresh();
        $this->assertSame('premium', $fresh->membership_level);
        // Ruolo WP ESATTO aggiornato; il ruolo app (permessi) resta invariato.
        $this->assertSame('presidente', $fresh->wp_role);
        $this->assertSame('Presidente', $fresh->wp_role_label);
        $this->assertSame('member', $fresh->role->value);
    }
}
