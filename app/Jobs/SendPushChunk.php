<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Notifications\Push\Contracts\AudiencePushTransport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Invia UN chunk di destinatari: o una lista di external id (≤ ALIAS_CHUNK,
 * limite OneSignal), o un segmento (target "tutti"). È l'unità di lavoro
 * eseguita dalla coda Redis; tanti SendPushChunk compongono un invio di massa.
 * Aggiorna i contatori aggregati (success/failure) della notifica.
 */
class SendPushChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<int,string>|null $externalIds null se si invia per segmento
     */
    public function __construct(
        public int $notificationId,
        public ?array $externalIds = null,
        public ?string $segment = null,
    ) {}

    public function handle(AudiencePushTransport $transport): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $notification = PushNotification::find($this->notificationId);
        if (! $notification) {
            return;
        }

        $message = $notification->toPushMessage();

        $result = $this->segment !== null
            ? $transport->sendToSegment($this->segment, $message)
            : $transport->sendToExternalIds($this->externalIds ?? [], $message);

        Cache::increment("push:{$this->notificationId}:success", $result->success);
        Cache::increment("push:{$this->notificationId}:failure", $result->failure);
    }
}
