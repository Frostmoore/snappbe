<?php

namespace App\Notifications\Push\Contracts;

use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;

/**
 * Astrazione del canale di invio push. Permette di sostituire/estendere il
 * transport (FCM, log in dev, in futuro altri) senza toccare il service.
 */
interface PushTransport
{
    /**
     * @param array<int,string> $tokens token dei device destinatari
     */
    public function send(array $tokens, PushMessage $message): PushResult;
}
