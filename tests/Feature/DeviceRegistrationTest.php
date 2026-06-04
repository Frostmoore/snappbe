<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_device_can_register(): void
    {
        $this->postJson('/api/v1/devices', ['fcm_token' => 'abc123', 'platform' => 'android'])
            ->assertOk();

        $this->assertDatabaseHas('devices', ['fcm_token' => 'abc123', 'user_id' => null]);
    }

    public function test_authenticated_registration_links_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/devices', ['fcm_token' => 'tok-user', 'platform' => 'ios'])
            ->assertOk();

        $this->assertDatabaseHas('devices', ['fcm_token' => 'tok-user', 'user_id' => $user->id]);
    }

    public function test_same_token_is_upserted_not_duplicated(): void
    {
        $this->postJson('/api/v1/devices', ['fcm_token' => 'dup'])->assertOk();
        $this->postJson('/api/v1/devices', ['fcm_token' => 'dup'])->assertOk();

        $this->assertSame(1, \App\Models\Device::where('fcm_token', 'dup')->count());
    }
}
