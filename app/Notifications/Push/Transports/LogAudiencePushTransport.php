<?php

namespace App\Notifications\Push\Transports;

use App\Notifications\Push\Contracts\AudiencePushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use Illuminate\Support\Facades\Log;

/**
 * Transport di fallback (dev / credenziali OneSignal assenti): non invia nulla,
 * logga il messaggio e l'audience. Così il flusso completo è esercitabile anche
 * senza chiavi reali.
 */
class LogAudiencePushTransport implements AudiencePushTransport
{
    public function sendToExternalIds(array $externalIds, PushMessage $message): PushResult
    {
        Log::info('[push:log] external ids', [
            'count' => count($externalIds),
            'title' => $message->title,
            'data'  => $message->dataPayload(),
        ]);

        return new PushResult(success: count($externalIds));
    }

    public function sendToSegment(string $segment, PushMessage $message): PushResult
    {
        Log::info('[push:log] segment', [
            'segment' => $segment,
            'title'   => $message->title,
            'data'    => $message->dataPayload(),
        ]);

        return new PushResult(success: 1);
    }
}
