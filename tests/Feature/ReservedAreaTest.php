<?php

namespace Tests\Feature;

use App\Models\ReservedElement;
use App\Models\ReservedSection;
use App\Models\ReservedTile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReservedAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiles_are_filtered_by_wp_role(): void
    {
        ReservedTile::create(['title' => 'Tutti', 'is_active' => true, 'visible_roles' => null, 'sort_order' => 1]);
        ReservedTile::create(['title' => 'Solo presidenti', 'is_active' => true, 'visible_roles' => ['presidente'], 'sort_order' => 2]);
        ReservedTile::create(['title' => 'Solo componenti', 'is_active' => true, 'visible_roles' => ['componente'], 'sort_order' => 3]);
        ReservedTile::create(['title' => 'Disattivata', 'is_active' => false, 'visible_roles' => null, 'sort_order' => 4]);

        Sanctum::actingAs(User::factory()->create(['wp_role' => 'presidente']));

        $this->getJson('/api/v1/reserved/tiles')
            ->assertOk()
            ->assertJsonCount(2, 'data') // "Tutti" + "Solo presidenti" (no componenti, no disattivata)
            ->assertJsonPath('data.0.title', 'Tutti')
            ->assertJsonPath('data.1.title', 'Solo presidenti');
    }

    public function test_unlinked_user_sees_only_public_tiles(): void
    {
        ReservedTile::create(['title' => 'Tutti', 'is_active' => true, 'visible_roles' => null, 'sort_order' => 1]);
        ReservedTile::create(['title' => 'Solo presidenti', 'is_active' => true, 'visible_roles' => ['presidente'], 'sort_order' => 2]);

        Sanctum::actingAs(User::factory()->create(['wp_role' => null]));

        $this->getJson('/api/v1/reserved/tiles')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Tutti');
    }

    public function test_tile_detail_filters_sections_and_elements_by_role(): void
    {
        $tile = ReservedTile::create(['title' => 'Documenti', 'is_active' => true, 'sort_order' => 1]);
        $secAll = ReservedSection::create(['reserved_tile_id' => $tile->id, 'title' => 'Generale', 'sort_order' => 1]);
        $secPres = ReservedSection::create(['reserved_tile_id' => $tile->id, 'title' => 'Riservata presidenti', 'visible_roles' => ['presidente'], 'sort_order' => 2]);
        $secOther = ReservedSection::create(['reserved_tile_id' => $tile->id, 'title' => 'Riservata componenti', 'visible_roles' => ['componente'], 'sort_order' => 3]);

        ReservedElement::create(['reserved_section_id' => $secAll->id, 'title' => 'Visibile a tutti', 'external_url' => 'https://x/a.pdf', 'sort_order' => 1]);
        ReservedElement::create(['reserved_section_id' => $secAll->id, 'title' => 'Solo componenti', 'external_url' => 'https://x/b.pdf', 'visible_roles' => ['componente'], 'sort_order' => 2]);

        Sanctum::actingAs(User::factory()->create(['wp_role' => 'presidente']));

        $res = $this->getJson("/api/v1/reserved/tiles/{$tile->id}")->assertOk();

        // Sezioni: Generale + Riservata presidenti (no componenti).
        $res->assertJsonCount(2, 'data.sections');
        // Nella sezione Generale, solo l'elemento pubblico (non quello dei componenti).
        $res->assertJsonCount(1, 'data.sections.0.elements')
            ->assertJsonPath('data.sections.0.elements.0.title', 'Visibile a tutti');
    }

    public function test_tiles_require_verified(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create());

        $this->getJson('/api/v1/reserved/tiles')->assertStatus(403);
    }
}
