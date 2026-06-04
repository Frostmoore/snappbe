<?php

namespace App\Services;

use App\Enums\PushTarget;
use App\Jobs\DispatchPushBatch;
use App\Models\AccessLevel;
use App\Models\Device;
use App\Models\PushNotification;
use App\Models\User;
use App\Notifications\Push\Contracts\PushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service UNIVERSALE per l'invio di notifiche push.
 *
 * Qualsiasi sorgente lo usa allo stesso modo: il pannello manuale (Fase 7) oggi,
 * e in futuro eventi/observer automatici (es. nuovo post). Disaccoppiato dal
 * transport (FCM/log) tramite PushTransport. Tutti i metodi accettano un
 * PushMessage che porta TUTTI i parametri (titolo, testo, immagine, deep-link...).
 */
class PushNotificationService
{
    public const BATCH = 500; // limite FCM per multicast = dimensione di un chunk

    public function __construct(private PushTransport $transport) {}

    /**
     * Invio di MASSA in coda (per centinaia di migliaia di destinatari).
     * Mette la notifica in stato "queued" e accoda l'orchestratore che spezza
     * i destinatari in batch gestibili eseguiti dalla coda Redis.
     */
    public function queue(PushNotification $notification): void
    {
        $notification->forceFill(['status' => 'queued'])->save();

        DispatchPushBatch::dispatch($notification->id);
    }

    /**
     * Itera i token dei destinatari a chunk di BATCH (memory-safe via chunkById),
     * senza mai caricarli tutti in memoria. Usato dall'orchestratore del batch.
     */
    public function eachTokenChunk(PushNotification $notification, callable $callback): void
    {
        $this->recipientDeviceQuery($notification)
            ->select(['id', 'fcm_token'])
            ->chunkById(self::BATCH, function ($devices) use ($callback) {
                $callback($devices->pluck('fcm_token')->all());
            });
    }

    /** Query dei device destinatari in base al target (senza eseguirla). */
    private function recipientDeviceQuery(PushNotification $notification): Builder
    {
        return match ($notification->target) {
            PushTarget::All   => Device::query(),
            PushTarget::Level => Device::query()->whereIn('user_id', $this->userIdsForLevel($notification->target_level)),
            PushTarget::Users => Device::query()->whereIn('user_id', $notification->target_user_ids ?? []),
        };
    }

    /** Subquery degli id utente con livello >= soglia (non carica gli id in PHP). */
    private function userIdsForLevel(?string $levelKey): Builder
    {
        $threshold = AccessLevel::weightFor($levelKey);
        $levelKeys = AccessLevel::query()->where('weight', '>=', $threshold)->pluck('key');

        return User::query()
            ->whereNotNull('membership_level')
            ->whereIn('membership_level', $levelKeys)
            ->select('id');
    }

    /** Invio diretto a una lista di token. */
    public function sendToTokens(array $tokens, PushMessage $message): PushResult
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        $result = new PushResult();

        foreach (array_chunk($tokens, self::BATCH) as $chunk) {
            $result = $result->merge($this->transport->send($chunk, $message));
        }

        $this->pruneInvalid($result->invalidTokens);

        return $result;
    }

    /** Invio agli utenti indicati (tutti i loro device). */
    public function sendToUsers(iterable $userIds, PushMessage $message): PushResult
    {
        $tokens = Device::query()
            ->whereIn('user_id', collect($userIds)->all())
            ->pluck('fcm_token')
            ->all();

        return $this->sendToTokens($tokens, $message);
    }

    /** Invio agli utenti con livello >= a quello indicato (questo livello e superiori). */
    public function sendToLevel(?string $levelKey, PushMessage $message): PushResult
    {
        $tokens = Device::query()
            ->whereIn('user_id', $this->userIdsForLevel($levelKey))
            ->pluck('fcm_token')
            ->all();

        return $this->sendToTokens($tokens, $message);
    }

    /** Invio a tutti i device registrati. */
    public function sendToAll(PushMessage $message): PushResult
    {
        return $this->sendToTokens(Device::query()->pluck('fcm_token')->all(), $message);
    }

    /**
     * Invio di una notifica composta (record del pannello): risolve il target,
     * invia e salva lo stato/le statistiche.
     */
    public function sendComposed(PushNotification $notification): PushResult
    {
        $message = $notification->toPushMessage();

        $result = match ($notification->target) {
            PushTarget::All   => $this->sendToAll($message),
            PushTarget::Level => $this->sendToLevel($notification->target_level, $message),
            PushTarget::Users => $this->sendToUsers($notification->target_user_ids ?? [], $message),
        };

        $notification->forceFill([
            'status'  => 'sent',
            'sent_at' => now(),
            'stats'   => $result->toArray(),
        ])->save();

        return $result;
    }

    /** Rimuove dal DB i token rifiutati da FCM (non più validi). */
    private function pruneInvalid(array $tokens): void
    {
        if ($tokens !== []) {
            Device::query()->whereIn('fcm_token', $tokens)->delete();
        }
    }
}
