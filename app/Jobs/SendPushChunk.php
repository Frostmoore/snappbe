<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\PushNotification;
use App\Notifications\Push\Contracts\PushTransport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Invia UN batch di token (max 500 = limite FCM multicast). È l'unità di lavoro
 * eseguita dalla coda Redis: tanti SendPushChunk compongono un invio di massa.
 * Aggiorna i contatori aggregati (success/failure/invalid) della notifica.
 */
class SendPushChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param array<int,string> $tokens */
    public function __construct(public int $notificationId, public array $tokens) {}

    public function handle(PushTransport $transport): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $notification = PushNotification::find($this->notificationId);
        if (! $notification) {
            return;
        }

        $result = $transport->send($this->tokens, $notification->toPushMessage());

        Cache::increment("push:{$this->notificationId}:success", $result->success);
        Cache::increment("push:{$this->notificationId}:failure", $result->failure);

        if ($result->invalidTokens !== []) {
            Cache::increment("push:{$this->notificationId}:invalid", count($result->invalidTokens));
            Device::query()->whereIn('fcm_token', $result->invalidTokens)->delete();
        }
    }
}
