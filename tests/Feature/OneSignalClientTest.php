<?php

namespace Tests\Feature;

use App\Notifications\Push\PushMessage;
use App\Services\Push\OneSignalClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneSignalClientTest extends TestCase
{
    public function test_builds_and_posts_expected_payload(): void
    {
        config([
            'snapp.onesignal.app_id' => 'app-123',
            'snapp.onesignal.rest_api_key' => 'key-abc',
            'snapp.onesignal.api_url' => 'https://onesignal.com/api/v1/notifications',
        ]);
        Http::fake(['*' => Http::response(['id' => 'n1', 'recipients' => 5], 200)]);

        $message = PushMessage::make('Ciao', 'Mondo')
            ->withDeepLink('snapp://article/9')
            ->withImage('https://x/y.jpg');

        $result = $this->app->make(OneSignalClient::class)->send($message, [
            'include_aliases' => ['external_id' => ['1', '2']],
            'target_channel' => 'push',
        ]);

        $this->assertSame(5, $result->success);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://onesignal.com/api/v1/notifications'
                && $request['app_id'] === 'app-123'
                && $request['headings']['en'] === 'Ciao'
                && $request['contents']['en'] === 'Mondo'
                && $request['include_aliases']['external_id'] === ['1', '2']
                && $request['target_channel'] === 'push'
                && $request['data']['deep_link'] === 'snapp://article/9'
                && $request['big_picture'] === 'https://x/y.jpg'
                && $request->hasHeader('Authorization', 'Basic key-abc');
        });
    }

    public function test_not_configured_does_not_send_and_returns_failure(): void
    {
        config(['snapp.onesignal.app_id' => '', 'snapp.onesignal.rest_api_key' => '']);
        Http::fake();

        $result = $this->app->make(OneSignalClient::class)->send(PushMessage::make('T', 'B'), []);

        $this->assertSame(1, $result->failure);
        Http::assertNothingSent();
    }

    public function test_onesignal_error_response_counts_as_failure(): void
    {
        config(['snapp.onesignal.app_id' => 'app-123', 'snapp.onesignal.rest_api_key' => 'key-abc']);
        Http::fake(['*' => Http::response(['errors' => ['Invalid players']], 200)]);

        $result = $this->app->make(OneSignalClient::class)->send(PushMessage::make('T', 'B'), [
            'include_aliases' => ['external_id' => ['1']],
        ]);

        $this->assertSame(1, $result->failure);
    }
}
