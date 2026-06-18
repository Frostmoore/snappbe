<?php

namespace Tests\Feature\Auth;

use App\Notifications\Auth\SnaPasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SnaPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_sends_code_when_email_exists_on_sna(): void
    {
        Notification::fake();
        Http::fake([
            '*/snapp/v1/account-by-email' => Http::response(['wp_user_id' => 5, 'email' => 'mario@sna.it'], 200),
        ]);

        $this->postJson('/api/v1/auth/sna/password/forgot', ['email' => 'mario@sna.it'])->assertOk();

        Notification::assertSentOnDemand(SnaPasswordResetCode::class);
        $this->assertDatabaseHas('sna_password_resets', ['email' => 'mario@sna.it', 'wp_user_id' => 5]);
    }

    public function test_request_is_generic_and_silent_when_email_not_on_sna(): void
    {
        Notification::fake();
        Http::fake(['*/snapp/v1/account-by-email' => Http::response(null, 404)]);

        $this->postJson('/api/v1/auth/sna/password/forgot', ['email' => 'ignota@x.it'])->assertOk();

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('sna_password_resets', ['email' => 'ignota@x.it']);
    }

    public function test_reset_sets_new_password_with_valid_code(): void
    {
        DB::table('sna_password_resets')->insert([
            'email' => 'mario@sna.it', 'token' => Hash::make('123456'), 'wp_user_id' => 5, 'created_at' => now(),
        ]);
        Http::fake(['*/snapp/v1/set-password' => Http::response(['ok' => true, 'wp_user_id' => 5], 200)]);

        $this->postJson('/api/v1/auth/sna/password/reset', [
            'email' => 'mario@sna.it', 'code' => '123456',
            'password' => 'NuovaPass1', 'password_confirmation' => 'NuovaPass1',
        ])->assertOk();

        $this->assertDatabaseMissing('sna_password_resets', ['email' => 'mario@sna.it']);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'set-password')
            && $r['wp_user_id'] === 5 && $r['new_password'] === 'NuovaPass1');
    }

    public function test_reset_rejects_wrong_code(): void
    {
        DB::table('sna_password_resets')->insert([
            'email' => 'mario@sna.it', 'token' => Hash::make('123456'), 'wp_user_id' => 5, 'created_at' => now(),
        ]);
        Http::fake();

        $this->postJson('/api/v1/auth/sna/password/reset', [
            'email' => 'mario@sna.it', 'code' => '000000',
            'password' => 'NuovaPass1', 'password_confirmation' => 'NuovaPass1',
        ])->assertStatus(422);

        Http::assertNothingSent();
        $this->assertDatabaseHas('sna_password_resets', ['email' => 'mario@sna.it']);
    }

    public function test_reset_rejects_expired_code(): void
    {
        DB::table('sna_password_resets')->insert([
            'email' => 'mario@sna.it', 'token' => Hash::make('123456'), 'wp_user_id' => 5,
            'created_at' => now()->subMinutes(45),
        ]);

        $this->postJson('/api/v1/auth/sna/password/reset', [
            'email' => 'mario@sna.it', 'code' => '123456',
            'password' => 'NuovaPass1', 'password_confirmation' => 'NuovaPass1',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('sna_password_resets', ['email' => 'mario@sna.it']);
    }
}
