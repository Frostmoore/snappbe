<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\HomeSection;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_loads_with_sections_widget(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);

        // Se il widget SectionsOverview avesse un errore, la dashboard restituirebbe 500.
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_home_sections_admin_page_loads_with_rows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
        HomeSection::create(['title' => 'Test', 'route' => '/articles', 'layout' => 'half', 'sort_order' => 0, 'is_active' => true]);

        // Con una riga, la tabella esegue le closure (formatStateUsing): verifica no 500.
        $this->actingAs($admin)->get('/admin/home-sections')->assertOk();
    }

    public function test_partners_admin_page_loads_with_rows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
        Partner::create(['name' => 'Test', 'type' => 'partner', 'sort_order' => 0, 'is_active' => true]);

        $this->actingAs($admin)->get('/admin/partners')->assertOk();
    }
}
