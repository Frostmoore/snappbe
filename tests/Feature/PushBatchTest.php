<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\PushNotification;
use App\Notifications\Push\Contracts\PushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushBatchTest extends TestCase
{
    use RefreshDatabase;

    private function fakeTransport(array $invalid = []): void
    {
        $fake = new class($invalid) implements PushTransport {
            public function __construct(private array $invalid) {}

            public function send(array $tokens, PushMessage $message): PushResult
            {
                return new PushResult(success: count($tokens), invalidTokens: $this->invalid);
            }
        };

        $this->app->instance(PushTransport::class, $fake);
    }

    public function test_queue_dispatches_batch_and_marks_sent_with_aggregated_stats(): void
    {
        $this->fakeTransport();
        Device::create(['fcm_token' => 'a']);
        Device::create(['fcm_token' => 'b']);
        Device::create(['fcm_token' => 'c']);

        $notification = PushNotification::create([
            'title' => 'Massa', 'body' => 'B', 'target' => 'all', 'status' => 'draft',
        ]);

        $this->app->make(PushNotificationService::class)->queue($notification);

        $fresh = $notification->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertSame(3, $fresh->stats['success']);
    }

    public function test_invalid_tokens_pruned_during_batch(): void
    {
        $this->fakeTransport(invalid: ['bad']);
        Device::create(['fcm_token' => 'bad']);
        Device::create(['fcm_token' => 'good']);

        $notification = PushNotification::create([
            'title' => 'T', 'body' => 'B', 'target' => 'all', 'status' => 'draft',
        ]);

        $this->app->make(PushNotificationService::class)->queue($notification);

        $this->assertDatabaseMissing('devices', ['fcm_token' => 'bad']);
        $this->assertDatabaseHas('devices', ['fcm_token' => 'good']);
    }
}
