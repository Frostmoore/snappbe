<?php

namespace Tests\Feature;

use App\Models\AccessLevel;
use App\Models\PushNotification;
use App\Models\User;
use App\Notifications\Push\Contracts\AudiencePushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Transport finto che registra external id / segmenti inviati. */
    private function fakeTransport(): object
    {
        $fake = new class implements AudiencePushTransport {
            public array $sentExternalIds = [];
            public array $segments = [];
            public ?PushMessage $lastMessage = null;

            public function sendToExternalIds(array $externalIds, PushMessage $message): PushResult
            {
                $this->sentExternalIds = array_merge($this->sentExternalIds, $externalIds);
                $this->lastMessage = $message;

                return new PushResult(success: count($externalIds));
            }

            public function sendToSegment(string $segment, PushMessage $message): PushResult
            {
                $this->segments[] = $segment;
                $this->lastMessage = $message;

                return new PushResult(success: 1);
            }
        };

        $this->app->instance(AudiencePushTransport::class, $fake);

        return $fake;
    }

    private function service(): PushNotificationService
    {
        return $this->app->make(PushNotificationService::class);
    }

    public function test_send_to_all_uses_segment(): void
    {
        $fake = $this->fakeTransport();

        $result = $this->service()->sendToAll(PushMessage::make('T', 'B'));

        $this->assertSame(['Total Subscriptions'], $fake->segments);
        $this->assertSame(1, $result->success);
    }

    public function test_send_to_level_targets_that_level_and_above(): void
    {
        $fake = $this->fakeTransport();
        AccessLevel::create(['key' => 'iscritto', 'label' => 'Iscritto', 'weight' => 10]);
        AccessLevel::create(['key' => 'premium', 'label' => 'Premium', 'weight' => 20]);

        $iscritto = User::factory()->create(['membership_level' => 'iscritto']);
        $premium = User::factory()->create(['membership_level' => 'premium']);
        User::factory()->create(['membership_level' => null]);

        $this->service()->sendToLevel('iscritto', PushMessage::make('T', 'B'));

        $this->assertEqualsCanonicalizing(
            [(string) $iscritto->id, (string) $premium->id],
            $fake->sentExternalIds,
        );
    }

    public function test_send_to_role_targets_users_with_that_wp_role(): void
    {
        $fake = $this->fakeTransport();
        $a = User::factory()->create(['wp_role' => 'agente']);
        $b = User::factory()->create(['wp_role' => 'agente']);
        User::factory()->create(['wp_role' => 'collaboratore']);
        User::factory()->create(['wp_role' => null]);

        $this->service()->sendToRole('agente', PushMessage::make('T', 'B'));

        $this->assertEqualsCanonicalizing([(string) $a->id, (string) $b->id], $fake->sentExternalIds);
    }

    public function test_send_to_users_targets_given_ids(): void
    {
        $fake = $this->fakeTransport();

        $this->service()->sendToUsers([7, 9], PushMessage::make('T', 'B'));

        $this->assertEqualsCanonicalizing(['7', '9'], $fake->sentExternalIds);
    }

    public function test_send_composed_updates_status_and_stats(): void
    {
        $this->fakeTransport();

        $notification = PushNotification::create([
            'title' => 'Avviso', 'body' => 'Testo', 'target' => 'all', 'status' => 'draft',
        ]);

        $this->service()->sendComposed($notification);

        $fresh = $notification->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertSame(1, $fresh->stats['success']);
        $this->assertNotNull($fresh->sent_at);
    }

    public function test_uploaded_image_path_is_resolved_to_public_url(): void
    {
        $notification = PushNotification::create([
            'title' => 'T', 'body' => 'B', 'target' => 'all', 'status' => 'draft',
            'image_path' => 'notifications/foto.jpg',
        ]);

        $this->assertStringContainsString('notifications/foto.jpg', $notification->toPushMessage()->image);
    }

    public function test_external_url_used_when_no_upload(): void
    {
        $notification = PushNotification::create([
            'title' => 'T', 'body' => 'B', 'target' => 'all', 'status' => 'draft',
            'image_url' => 'https://esterno/x.jpg',
        ]);

        $this->assertSame('https://esterno/x.jpg', $notification->toPushMessage()->image);
    }

    public function test_push_message_carries_all_params_in_data(): void
    {
        $fake = $this->fakeTransport();

        $message = PushMessage::make('T', 'B')
            ->withImage('https://x/y.jpg')
            ->withDeepLink('snapp://article/9')
            ->withData(['campagna' => 'estate']);

        $this->service()->sendToUsers([1], $message);

        $this->assertSame('https://x/y.jpg', $fake->lastMessage->image);
        $this->assertSame('snapp://article/9', $fake->lastMessage->dataPayload()['deep_link']);
        $this->assertSame('estate', $fake->lastMessage->dataPayload()['campagna']);
    }
}
