<?php

namespace App\Notifications\Push\Contracts;

use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;

/**
 * Astrazione del canale push basata sull'AUDIENCE (non sui token).
 *
 * È il modello di OneSignal: si indica CHI deve ricevere (external user id =
 * id utente app, oppure un segmento) e il provider risolve i device. Permette di
 * sostituire il provider (OneSignal oggi, log in dev, altri domani) senza
 * toccare il PushNotificationService.
 */
interface AudiencePushTransport
{
    /**
     * Invia agli utenti indicati tramite il loro external id (= id utente app).
     *
     * @param array<int,string> $externalIds
     */
    public function sendToExternalIds(array $externalIds, PushMessage $message): PushResult;

    /** Invia a un segmento OneSignal (es. "Total Subscriptions" per "tutti"). */
    public function sendToSegment(string $segment, PushMessage $message): PushResult;
}
