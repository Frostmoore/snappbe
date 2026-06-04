<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Services\PushNotificationService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

/**
 * Orchestratore dell'invio di massa: gira sulla coda (NON nel web), risolve i
 * destinatari a CHUNK (memory-safe via chunkById) e crea un Bus batch di
 * SendPushChunk eseguiti dalla coda Redis. Al termine del batch aggrega le
 * statistiche e marca la notifica come inviata.
 *
 * Pensato per centinaia di migliaia di destinatari: nessun token viene caricato
 * tutto in memoria nel web; i contatori sono atomici (Cache/Redis).
 */
class DispatchPushBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $notificationId) {}

    public function handle(PushNotificationService $service): void
    {
        $notification = PushNotification::find($this->notificationId);
        if (! $notification || $notification->status === 'sent') {
            return;
        }

        $id = $this->notificationId;

        // Azzera i contatori aggregati.
        Cache::forever("push:{$id}:success", 0);
        Cache::forever("push:{$id}:failure", 0);
        Cache::forever("push:{$id}:invalid", 0);

        // Costruisce un job per ogni chunk di token (≤500).
        $jobs = [];
        $service->eachTokenChunk($notification, function (array $tokens) use (&$jobs, $id) {
            $jobs[] = new SendPushChunk($id, $tokens);
        });

        if ($jobs === []) {
            $notification->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
                'stats' => ['success' => 0, 'failure' => 0, 'invalid' => 0],
            ])->save();

            return;
        }

        Bus::batch($jobs)
            ->name("push-{$id}")
            ->allowFailures()
            ->finally(function (Batch $batch) use ($id) {
                $notification = PushNotification::find($id);
                if ($notification) {
                    $notification->forceFill([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'stats' => [
                            'success' => (int) Cache::get("push:{$id}:success", 0),
                            'failure' => (int) Cache::get("push:{$id}:failure", 0),
                            'invalid' => (int) Cache::get("push:{$id}:invalid", 0),
                        ],
                    ])->save();
                }
            })
            ->dispatch();
    }
}
