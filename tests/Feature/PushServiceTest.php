<?php

namespace Tests\Feature;

use App\Models\AccessLevel;
use App\Models\Device;
use App\Models\PushNotification;
use App\Models\User;
use App\Notifications\Push\Contracts\PushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Transport finto che registra i token inviati. */
    private function fakeTransport(array $invalid = []): object
    {
        $fake = new class($invalid) implements PushTransport {
            public array $sentTokens = [];
            public ?PushMessage $lastMessage = null;

            public function __construct(private array $invalid) {}

            public function send(array $tokens, PushMessage $message): PushResult
            {
                $this->sentTokens = array_merge($this->sentTokens, $tokens);
                $this->lastMessage = $message;

                return new PushResult(success: count($tokens), invalidTokens: $this->invalid);
            }
        };

        $this->app->instance(PushTransport::class, $fake);

        return $fake;
    }

    private function service(): PushNotificationService
    {
        return $this->app->make(PushNotificationService::class);
    }

    public function test_send_to_all_reaches_every_device_including_anonymous(): void
    {
        $fake = $this->fakeTransport();
        $user = User::factory()->create();
        Device::create(['user_id' => $user->id, 'fcm_token' => 'tok1']);
        Device::create(['fcm_token' => 'tok2']); // anonimo

        $result = $this->service()->sendToAll(PushMessage::make('T', 'B'));

        $this->assertEqualsCanonicalizing(['tok1', 'tok2'], $fake->sentTokens);
        $this->assertSame(2, $result->success);
    }

    public function test_send_to_level_targets_that_level_and_above(): void
    {
        $fake = $this->fakeTransport();
        AccessLevel::create(['key' => 'iscritto', 'label' => 'Iscritto', 'weight' => 10]);
        AccessLevel::create(['key' => 'premium', 'label' => 'Premium', 'weight' => 20]);

        $iscritto = User::factory()->create(['membership_level' => 'iscritto']);
        $premium = User::factory()->create(['membership_level' => 'premium']);
        $nessuno = User::factory()->create(['membership_level' => null]);
        Device::create(['user_id' => $iscritto->id, 'fcm_token' => 't-iscritto']);
        Device::create(['user_id' => $premium->id, 'fcm_token' => 't-premium']);
        Device::create(['user_id' => $nessuno->id, 'fcm_token' => 't-nessuno']);

        $this->service()->sendToLevel('iscritto', PushMessage::make('T', 'B'));

        $this->assertEqualsCanonicalizing(['t-iscritto', 't-premium'], $fake->sentTokens);
    }

    public function test_send_composed_updates_status_and_stats(): void
    {
        $this->fakeTransport();
        Device::create(['fcm_token' => 'tok1']);

        $notification = PushNotification::create([
            'title' => 'Avviso', 'body' => 'Testo', 'target' => 'all', 'status' => 'draft',
        ]);

        $this->service()->sendComposed($notification);

        $fresh = $notification->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertSame(1, $fresh->stats['success']);
        $this->assertNotNull($fresh->sent_at);
    }

    public function test_invalid_tokens_are_pruned(): void
    {
        $this->fakeTransport(invalid: ['bad-token']);
        Device::create(['fcm_token' => 'bad-token']);
        Device::create(['fcm_token' => 'good-token']);

        $this->service()->sendToAll(PushMessage::make('T', 'B'));

        $this->assertDatabaseMissing('devices', ['fcm_token' => 'bad-token']);
        $this->assertDatabaseHas('devices', ['fcm_token' => 'good-token']);
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
        Device::create(['fcm_token' => 'tok1']);

        $message = PushMessage::make('T', 'B')
            ->withImage('https://x/y.jpg')
            ->withDeepLink('snapp://article/9')
            ->withData(['campagna' => 'estate']);

        $this->service()->sendToAll($message);

        $this->assertSame('https://x/y.jpg', $fake->lastMessage->image);
        $this->assertSame('snapp://article/9', $fake->lastMessage->dataPayload()['deep_link']);
        $this->assertSame('estate', $fake->lastMessage->dataPayload()['campagna']);
    }
}
