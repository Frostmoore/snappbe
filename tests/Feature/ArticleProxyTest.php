<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleProxyTest extends TestCase
{
    private function fakeArticles(): void
    {
        Http::fake([
            '*/snapp/v1/articles/5' => Http::response([
                'id' => 5, 'title' => 'Articolo singolo', 'content' => '<p>corpo</p>',
            ], 200),
            '*/snapp/v1/articles*' => Http::response([
                ['id' => 1, 'title' => 'Primo', 'excerpt' => 'a'],
                ['id' => 2, 'title' => 'Secondo', 'excerpt' => 'b'],
            ], 200, ['X-WP-Total' => '2', 'X-WP-TotalPages' => '1']),
        ]);
    }

    public function test_index_returns_proxied_articles_with_meta(): void
    {
        $this->fakeArticles();

        $this->getJson('/api/v1/articles')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Primo')
            ->assertJsonPath('data.0.type', 'article')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.total_pages', 1);
    }

    public function test_show_returns_single_article(): void
    {
        $this->fakeArticles();

        $this->getJson('/api/v1/articles/5')
            ->assertOk()
            ->assertJsonPath('data.id', 5)
            ->assertJsonPath('data.content', '<p>corpo</p>');
    }

    public function test_index_is_cached_and_hits_wordpress_once(): void
    {
        $this->fakeArticles();

        $this->getJson('/api/v1/articles')->assertOk();
        $this->getJson('/api/v1/articles')->assertOk();

        // La seconda richiesta arriva dalla cache: WP interrogato una sola volta.
        Http::assertSentCount(1);
    }

    public function test_returns_503_when_wordpress_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));

        $this->getJson('/api/v1/articles')->assertStatus(503);
    }
}
