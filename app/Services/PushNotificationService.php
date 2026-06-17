<?php

namespace App\Services;

use App\Enums\PushTarget;
use App\Jobs\DispatchPushBatch;
use App\Models\AccessLevel;
use App\Models\PushNotification;
use App\Models\User;
use App\Notifications\Push\Contracts\AudiencePushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service UNIVERSALE e RIUSABILE per l'invio di notifiche push.
 *
 * Qualunque sorgente lo usa allo stesso modo: oggi il pannello manuale (Fase 7),
 * domani observer/eventi automatici (es. nuovo post, scadenza, ecc.). È
 * disaccoppiato dal provider tramite AudiencePushTransport (OneSignal/log): qui
 * c'è SOLO la logica "chi sono i destinatari", non "come si invia".
 *
 * Modello OneSignal: si targetizza per **external user id** (= id utente app,
 * impostato dall'app con OneSignal.login) o per **segmento** (target "tutti").
 */
class PushNotificationService
{
    /** Limite di external id per richiesta OneSignal (include_aliases). */
    public const ALIAS_CHUNK = 2000;

    public function __construct(private AudiencePushTransport $transport) {}

    /**
     * Invio di MASSA in coda (per grandi volumi). Mette la notifica in "queued" e
     * accoda l'orchestratore che spezza i destinatari in batch sulla coda Redis.
     */
    public function queue(PushNotification $notification): void
    {
        $notification->forceFill(['status' => 'queued'])->save();

        DispatchPushBatch::dispatch($notification->id);
    }

    /** Invio diretto a una lista di external id (chunk a ALIAS_CHUNK). */
    public function sendToExternalIds(array $externalIds, PushMessage $message): PushResult
    {
        $externalIds = array_values(array_unique(array_filter(array_map('strval', $externalIds), static fn ($id) => $id !== '')));
        $result = new PushResult();

        foreach (array_chunk($externalIds, self::ALIAS_CHUNK) as $chunk) {
            $result = $result->merge($this->transport->sendToExternalIds($chunk, $message));
        }

        return $result;
    }

    /** Invio agli utenti indicati (per id utente app). */
    public function sendToUsers(iterable $userIds, PushMessage $message): PushResult
    {
        return $this->sendToExternalIds(collect($userIds)->all(), $message);
    }

    /** Invio agli utenti con livello >= a quello indicato (questo livello e superiori). */
    public function sendToLevel(?string $levelKey, PushMessage $message): PushResult
    {
        return $this->sendToExternalIds($this->userIdsForLevel($levelKey)->pluck('id')->all(), $message);
    }

    /** Invio agli utenti con un dato ruolo WordPress (slug esatto). */
    public function sendToRole(?string $roleSlug, PushMessage $message): PushResult
    {
        if ($roleSlug === null || $roleSlug === '') {
            return new PushResult();
        }

        return $this->sendToExternalIds(
            User::query()->where('wp_role', $roleSlug)->pluck('id')->all(),
            $message,
        );
    }

    /** Invio a tutti gli iscritti (segmento OneSignal). */
    public function sendToAll(PushMessage $message): PushResult
    {
        return $this->transport->sendToSegment(
            (string) config('snapp.onesignal.all_segment', 'Total Subscriptions'),
            $message,
        );
    }

    /**
     * Invio di una notifica composta (record del pannello): risolve il target,
     * invia e salva stato/statistiche.
     */
    public function sendComposed(PushNotification $notification): PushResult
    {
        $message = $notification->toPushMessage();

        $result = match ($notification->target) {
            PushTarget::All   => $this->sendToAll($message),
            PushTarget::Level => $this->sendToLevel($notification->target_level, $message),
            PushTarget::Role  => $this->sendToRole($notification->target_role, $message),
            PushTarget::Users => $this->sendToUsers($notification->target_user_ids ?? [], $message),
        };

        $notification->forceFill([
            'status'  => 'sent',
            'sent_at' => now(),
            'stats'   => $result->toArray(),
        ])->save();

        return $result;
    }

    /**
     * Itera gli external id dei destinatari a chunk di ALIAS_CHUNK (memory-safe).
     * Ritorna `null` come chunk per il target "tutti" (gestito via segmento, non
     * via lista di id). Usato dall'orchestratore del batch.
     *
     * @param callable(array<int,string>|null):void $callback
     */
    public function eachExternalIdChunk(PushNotification $notification, callable $callback): void
    {
        if ($notification->target === PushTarget::All) {
            $callback(null); // segnale: invio per segmento

            return;
        }

        $this->recipientUserQuery($notification)
            ->select('id')
            ->chunkById(self::ALIAS_CHUNK, function ($users) use ($callback) {
                $callback($users->pluck('id')->map(static fn ($id) => (string) $id)->all());
            });
    }

    /** Query degli utenti destinatari per target Level/Users (senza eseguirla). */
    private function recipientUserQuery(PushNotification $notification): Builder
    {
        return match ($notification->target) {
            PushTarget::Level => $this->userIdsForLevel($notification->target_level),
            PushTarget::Role  => User::query()->where('wp_role', $notification->target_role),
            PushTarget::Users => User::query()->whereIn('id', $notification->target_user_ids ?? []),
            PushTarget::All   => User::query()->whereRaw('1 = 0'), // non usato (vedi eachExternalIdChunk)
        };
    }

    /** Query degli utenti con livello >= soglia (questo livello e superiori). */
    private function userIdsForLevel(?string $levelKey): Builder
    {
        $threshold = AccessLevel::weightFor($levelKey);
        $levelKeys = AccessLevel::query()->where('weight', '>=', $threshold)->pluck('key');

        return User::query()
            ->whereNotNull('membership_level')
            ->whereIn('membership_level', $levelKeys);
    }
}
