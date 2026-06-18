<?php

namespace Tests\Feature;

use App\Models\AccessLevel;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostFeedTest extends TestCase
{
    use RefreshDatabase;

    private function levels(): void
    {
        AccessLevel::create(['key' => 'iscritto', 'label' => 'Iscritto', 'weight' => 10]);
        AccessLevel::create(['key' => 'premium', 'label' => 'Premium', 'weight' => 20]);
    }

    private function makePosts(): void
    {
        Post::create(['type' => 'news', 'title' => 'Pubblico', 'slug' => 'pub', 'status' => 'published', 'published_at' => now()->subDay(), 'audience' => 'all']);
        Post::create(['type' => 'news', 'title' => 'Riservato', 'slug' => 'ris', 'status' => 'published', 'published_at' => now()->subDay(), 'audience' => 'level', 'min_level' => 'iscritto']);
        Post::create(['type' => 'news', 'title' => 'Bozza', 'slug' => 'boz', 'status' => 'draft', 'audience' => 'all']);
    }

    public function test_anonymous_sees_only_public_published(): void
    {
        $this->levels();
        $this->makePosts();

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Pubblico');
    }

    public function test_member_with_level_sees_reserved(): void
    {
        $this->levels();
        $this->makePosts();
        Sanctum::actingAs(User::factory()->create(['membership_level' => 'iscritto']));

        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_role_audience_visible_only_to_that_role(): void
    {
        Post::create(['type' => 'news', 'title' => 'Agenti', 'slug' => 'ag', 'status' => 'published', 'published_at' => now()->subDay(), 'audience' => 'role', 'audience_role' => 'agente']);

        Sanctum::actingAs(User::factory()->create(['wp_role' => 'agente']));
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Agenti');

        Sanctum::actingAs(User::factory()->create(['wp_role' => 'collaboratore']));
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_users_audience_visible_only_to_listed_users(): void
    {
        $target = User::factory()->create();
        Post::create(['type' => 'news', 'title' => 'Solo lui', 'slug' => 'solo', 'status' => 'published', 'published_at' => now()->subDay(), 'audience' => 'users', 'audience_user_ids' => [$target->id]]);

        Sanctum::actingAs($target);
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_show_reserved_post_forbidden_for_wrong_audience(): void
    {
        $this->levels();
        $post = Post::create(['type' => 'news', 'title' => 'R', 'slug' => 'r', 'status' => 'published', 'published_at' => now()->subDay(), 'audience' => 'level', 'min_level' => 'premium']);

        $this->getJson("/api/v1/posts/{$post->id}")->assertStatus(403);
    }

    public function test_show_role_post_forbidden_for_other_role(): void
    {
        $post = Post::create(['type' => 'news', 'title' => 'R', 'slug' => 'r2', 'status' => 'published', 'published_at' => now()->subDay(), 'audience' => 'role', 'audience_role' => 'agente']);
        Sanctum::actingAs(User::factory()->create(['wp_role' => 'altro']));

        $this->getJson("/api/v1/posts/{$post->id}")->assertStatus(403);
    }

    public function test_show_public_post_includes_body(): void
    {
        $post = Post::create(['type' => 'news', 'title' => 'P', 'slug' => 'p', 'body' => '<p>ciao</p>', 'status' => 'published', 'published_at' => now()->subDay(), 'audience' => 'all']);

        $this->getJson("/api/v1/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.body', '<p>ciao</p>');
    }
}
