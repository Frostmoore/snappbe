<?php

namespace Tests\Feature;

use App\Models\AppSettings;
use App\Models\SocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_endpoint_returns_data(): void
    {
        AppSettings::current()->update(['app_name' => 'SNAPP', 'primary_color' => '#003366']);

        $this->getJson('/api/v1/app/settings')
            ->assertOk()
            ->assertJsonPath('data.app_name', 'SNAPP')
            ->assertJsonPath('data.primary_color', '#003366');
    }

    public function test_social_links_returns_only_active_ordered(): void
    {
        SocialLink::create(['platform' => 'facebook', 'url' => 'https://fb', 'sort_order' => 2, 'is_active' => true]);
        SocialLink::create(['platform' => 'instagram', 'url' => 'https://ig', 'sort_order' => 1, 'is_active' => true]);
        SocialLink::create(['platform' => 'x', 'url' => 'https://x', 'sort_order' => 0, 'is_active' => false]);

        $this->getJson('/api/v1/app/social-links')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.platform', 'instagram')
            ->assertJsonPath('data.1.platform', 'facebook');
    }
}
