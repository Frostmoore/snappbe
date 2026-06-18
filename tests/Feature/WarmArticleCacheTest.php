<?php

namespace Tests\Feature;

use App\Services\WordPress\ArticleCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WarmArticleCacheTest extends TestCase
{
    public function test_warm_populates_articles_and_newsletters_cache(): void
    {
        config(['snapp.wordpress.base_url' => 'https://sna.test']);
        Http::fake([
            '*/snapp/v1/articles*' => Http::response(
                [['id' => 1, 'title' => 'Ciao', 'excerpt' => '', 'slug' => 'ciao', 'image' => null]],
                200,
                ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1'],
            ),
        ]);

        $this->artisan('snapp:warm-articles')->assertSuccessful();

        $this->assertNotNull(Cache::get(ArticleCache::key('index:p1:pp15:s'.md5(''))));
        $this->assertNotNull(Cache::get(ArticleCache::key('newsletters:p1:pp15')));
    }
}
