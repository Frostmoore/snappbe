<?php

namespace Tests\Feature;

use App\Models\MagazineIssue;
use App\Models\OrgChartMember;
use App\Models\Partner;
use App\Models\ProvincialSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_provincial_sections_search_and_filter(): void
    {
        ProvincialSection::create(['name' => 'Sezione Milano', 'province' => 'MI', 'is_active' => true]);
        ProvincialSection::create(['name' => 'Sezione Roma', 'province' => 'RM', 'is_active' => true]);
        ProvincialSection::create(['name' => 'Disattiva', 'province' => 'MI', 'is_active' => false]);

        $this->getJson('/api/v1/provincial-sections')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/provincial-sections?province=MI')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/provincial-sections?search=Roma')->assertOk()->assertJsonPath('data.0.name', 'Sezione Roma');
    }

    public function test_partners_filter_by_type(): void
    {
        Partner::create(['name' => 'Conv A', 'type' => 'convenzione', 'is_active' => true]);
        Partner::create(['name' => 'Part B', 'type' => 'partner', 'is_active' => true]);

        $this->getJson('/api/v1/partners')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/partners?type=convenzione')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.type', 'convenzione');
    }

    public function test_magazine_issues_list(): void
    {
        MagazineIssue::create(['title' => 'N.1', 'url' => 'https://x/1', 'sort_order' => 1, 'is_active' => true]);
        MagazineIssue::create(['title' => 'Nascosto', 'url' => 'https://x/2', 'is_active' => false]);

        $this->getJson('/api/v1/magazine-issues')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'N.1');
    }

    public function test_org_chart_grouped_by_section(): void
    {
        OrgChartMember::create(['name' => 'Andrea', 'group' => 'Direzione', 'role' => 'Direttore', 'is_active' => true, 'sort_order' => 0]);
        OrgChartMember::create(['name' => 'Alberto', 'group' => 'Ufficio Legale', 'is_active' => true, 'sort_order' => 10]);
        OrgChartMember::create(['name' => 'Gianluigi', 'group' => 'Ufficio Legale', 'is_active' => true, 'sort_order' => 11]);
        OrgChartMember::create(['name' => 'Nascosto', 'group' => 'Direzione', 'is_active' => false, 'sort_order' => 1]);

        $this->getJson('/api/v1/org-chart')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.group', 'Direzione')
            ->assertJsonPath('data.0.members.0.name', 'Andrea')
            ->assertJsonPath('data.0.members.0.role', 'Direttore')
            ->assertJsonCount(1, 'data.0.members') // il membro non attivo è escluso
            ->assertJsonPath('data.1.group', 'Ufficio Legale')
            ->assertJsonCount(2, 'data.1.members');
    }

    public function test_events_list_and_show_proxy_wp(): void
    {
        Cache::flush();
        Http::fake([
            '*/snapp/v1/events/99' => Http::response(null, 404),
            '*/snapp/v1/events/7' => Http::response([
                'id' => 7, 'title' => 'Convegno', 'slug' => 'convegno', 'link' => 'https://sna.test/convegno',
                'image' => null, 'address' => 'Via Roma 1', 'starts_at' => now()->addDay()->toIso8601String(),
                'region' => 'Lazio', 'province' => 'Roma', 'description' => 'Dettagli', 'type' => 'formativo', 'type_label' => 'Formativo',
            ], 200),
            '*/snapp/v1/events*' => Http::response([
                ['id' => 7, 'title' => 'Convegno', 'slug' => 'convegno', 'link' => 'https://sna.test/convegno', 'type' => 'formativo', 'type_label' => 'Formativo', 'region' => 'Lazio', 'province' => 'Roma'],
            ], 200, ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1']),
        ]);

        $this->getJson('/api/v1/events')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.type', 'formativo');
        $this->getJson('/api/v1/events/7')->assertOk()
            ->assertJsonPath('data.slug', 'convegno')
            ->assertJsonPath('data.location', 'Via Roma 1')
            ->assertJsonPath('data.registration_url', 'https://sna.test/convegno');
        $this->getJson('/api/v1/events/99')->assertStatus(404);
    }

    public function test_events_503_when_wp_down(): void
    {
        Cache::flush();
        Http::fake(['*/snapp/v1/events*' => Http::response('boom', 500)]);

        $this->getJson('/api/v1/events')->assertStatus(503);
    }

    public function test_events_refresh_bypasses_cache(): void
    {
        Cache::flush();
        $headers = ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1'];
        Http::fake(['*/snapp/v1/events*' => Http::sequence()
            ->push([['id' => 1, 'title' => 'Vecchio', 'slug' => 'a']], 200, $headers)
            ->push([['id' => 2, 'title' => 'Nuovo', 'slug' => 'b']], 200, $headers)]);

        $this->getJson('/api/v1/events')->assertJsonPath('data.0.title', 'Vecchio'); // 1ª chiamata → cache
        $this->getJson('/api/v1/events')->assertJsonPath('data.0.title', 'Vecchio'); // servita da cache (no 2ª chiamata WP)
        $this->getJson('/api/v1/events?refresh=1')->assertJsonPath('data.0.title', 'Nuovo'); // bypassa → 2ª risposta WP
    }

    public function test_newsletters_proxy_uses_category(): void
    {
        Http::fake([
            '*/snapp/v1/articles*' => Http::response([['id' => 1, 'title' => 'Newsletter Giugno']], 200, ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1']),
        ]);

        $this->getJson('/api/v1/newsletters')->assertOk()->assertJsonPath('data.0.title', 'Newsletter Giugno');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'category=newsletter'));
    }
}
