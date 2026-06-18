<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email con il codice per reimpostare la password del sito SNA (reset in-app).
 * Accodata come le altre email transazionali.
 */
class SnaPasswordResetCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $code) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Codice per reimpostare la password SNA')
            ->greeting('Reset password SNA')
            ->line('Hai richiesto di reimpostare la password del tuo account sul sito SNA.')
            ->line('Il tuo codice di verifica è:')
            ->line('**'.$this->code.'**')
            ->line('Inseriscilo nell\'app per impostare una nuova password. Il codice scade tra 30 minuti.')
            ->line('Se non hai richiesto tu il reset, ignora questa email.');
    }
}
