<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\MagazineIssue;
use App\Models\OrgChartMember;
use App\Models\Partner;
use App\Models\ProvincialSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_org_chart_returns_tree(): void
    {
        $boss = OrgChartMember::create(['name' => 'Presidente', 'is_active' => true, 'sort_order' => 0]);
        OrgChartMember::create(['name' => 'Vice', 'parent_id' => $boss->id, 'is_active' => true, 'sort_order' => 0]);

        $this->getJson('/api/v1/org-chart')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Presidente')
            ->assertJsonPath('data.0.children.0.name', 'Vice');
    }

    public function test_events_list_and_show(): void
    {
        $pub = Event::create(['title' => 'Convegno', 'slug' => 'convegno', 'starts_at' => now()->addDay(), 'is_published' => true]);
        $draft = Event::create(['title' => 'Bozza', 'slug' => 'bozza', 'starts_at' => now()->addDay(), 'is_published' => false]);

        $this->getJson('/api/v1/events')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/events/{$pub->id}")->assertOk()->assertJsonPath('data.slug', 'convegno');
        $this->getJson("/api/v1/events/{$draft->id}")->assertStatus(404);
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
