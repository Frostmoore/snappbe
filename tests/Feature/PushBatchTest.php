<?php

namespace Tests\Feature;

use App\Models\PushNotification;
use App\Models\User;
use App\Notifications\Push\Contracts\AudiencePushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushBatchTest extends TestCase
{
    use RefreshDatabase;

    private function fakeTransport(): object
    {
        $fake = new class implements AudiencePushTransport {
            public array $sentExternalIds = [];
            public array $segments = [];

            public function sendToExternalIds(array $externalIds, PushMessage $message): PushResult
            {
                $this->sentExternalIds = array_merge($this->sentExternalIds, $externalIds);

                return new PushResult(success: count($externalIds));
            }

            public function sendToSegment(string $segment, PushMessage $message): PushResult
            {
                $this->segments[] = $segment;

                return new PushResult(success: 1);
            }
        };

        $this->app->instance(AudiencePushTransport::class, $fake);

        return $fake;
    }

    public function test_queue_all_dispatches_segment_and_marks_sent(): void
    {
        $fake = $this->fakeTransport();

        $notification = PushNotification::create([
            'title' => 'Massa', 'body' => 'B', 'target' => 'all', 'status' => 'draft',
        ]);

        $this->app->make(PushNotificationService::class)->queue($notification);

        $fresh = $notification->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertSame(1, $fresh->stats['success']);
        $this->assertContains('Total Subscriptions', $fake->segments);
    }

    public function test_queue_users_dispatches_external_id_chunks(): void
    {
        $fake = $this->fakeTransport();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $notification = PushNotification::create([
            'title' => 'M', 'body' => 'B', 'target' => 'users',
            'target_user_ids' => [$u1->id, $u2->id], 'status' => 'draft',
        ]);

        $this->app->make(PushNotificationService::class)->queue($notification);

        $fresh = $notification->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertSame(2, $fresh->stats['success']);
        $this->assertEqualsCanonicalizing([(string) $u1->id, (string) $u2->id], $fake->sentExternalIds);
    }
}
