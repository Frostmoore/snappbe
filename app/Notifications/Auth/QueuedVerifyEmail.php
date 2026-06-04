<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Email di verifica accodata sulla coda (non bloccante). Mantiene l'URL deep-link
 * configurato in AppServiceProvider (VerifyEmail::createUrlUsing).
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
