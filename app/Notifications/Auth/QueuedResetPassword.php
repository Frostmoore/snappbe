<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Email di reset password accodata sulla coda. Mantiene il deep-link configurato
 * in AppServiceProvider (ResetPassword::createUrlUsing).
 */
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;
}
