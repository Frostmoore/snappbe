<?php

namespace App\Notifications\Push\Transports;

use App\Notifications\Push\Contracts\PushTransport;
use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use Illuminate\Support\Facades\Log;

/**
 * Transport di fallback: scrive su log invece di inviare davvero.
 * Usato finché le credenziali Firebase non sono configurate, così il pannello
 * e il flusso funzionano già in sviluppo.
 */
class LogPushTransport implements PushTransport
{
    public function send(array $tokens, PushMessage $message): PushResult
    {
        Log::info('[push:log] invio simulato', [
            'tokens' => count($tokens),
            'title' => $message->title,
            'body' => $message->body,
            'image' => $message->image,
            'data' => $message->dataPayload(),
        ]);

        return new PushResult(success: count($tokens));
    }
}
