<?php

namespace Tests\Feature;

use App\Services\WordPress\ArticleCache;
use Tests\TestCase;

class WordPressWebhookTest extends TestCase
{
    private string $secret = 'test-hmac-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['snapp.wordpress.hmac_secret' => $this->secret]);
    }

    private function sendWebhook(array $payload, ?string $signature = null)
    {
        $body = json_encode($payload);
        $signature ??= hash_hmac('sha256', $body, $this->secret);

        return $this->call(
            'POST',
            '/api/v1/webhooks/wordpress',
            [], [], [],
            ['HTTP_X_SNAPP_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $body
        );
    }

    public function test_valid_signature_bumps_cache_version(): void
    {
        $before = ArticleCache::version();

        $this->sendWebhook(['event' => 'post.published', 'wp_post_id' => 9, 'timestamp' => time()])
            ->assertOk();

        $this->assertGreaterThan($before, ArticleCache::version());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->sendWebhook(['event' => 'post.published', 'wp_post_id' => 9], 'firma-sbagliata')
            ->assertStatus(401);
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        $this->sendWebhook(['event' => 'post.updated', 'wp_post_id' => 9, 'timestamp' => time() - 1000])
            ->assertStatus(401);
    }
}
