<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

/**
 * Transport mail di Laravel che spedisce via l'API email di OneSignal.
 *
 * Impostando MAIL_MAILER=onesignal, TUTTE le email transazionali (verifica
 * account, reset password app, codice reset SNA, ...) passano da OneSignal,
 * senza toccare il codice che le genera. Richiede che in OneSignal sia
 * configurato il canale Email con un dominio mittente verificato.
 */
class OneSignalMailTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $recipients = array_map(static fn (Address $a) => $a->getAddress(), $email->getTo());
        if ($recipients === []) {
            return;
        }

        $html = $email->getHtmlBody();
        if (! is_string($html) || $html === '') {
            $html = nl2br(htmlspecialchars((string) $email->getTextBody(), ENT_QUOTES));
        }

        $payload = [
            'app_id'               => (string) config('snapp.onesignal.app_id'),
            'email_subject'        => (string) $email->getSubject(),
            'email_body'           => $html,
            'include_email_tokens' => array_values($recipients),
        ];

        $from = $email->getFrom()[0] ?? null;
        if ($from instanceof Address) {
            $payload['email_from_address'] = $from->getAddress();
            if ($from->getName() !== '') {
                $payload['email_from_name'] = $from->getName();
            }
        }

        $response = Http::withHeaders([
            'Authorization' => 'Key '.(string) config('snapp.onesignal.rest_api_key'),
            'Content-Type'  => 'application/json; charset=utf-8',
        ])->timeout(20)->post((string) config('snapp.onesignal.api_url'), $payload);

        $body = $response->json();
        if (! $response->successful() || (is_array($body) && isset($body['errors']))) {
            // Far fallire l'invio: il job in coda verrà ritentato.
            throw new \RuntimeException('Invio email OneSignal fallito: '.$response->body());
        }
    }

    public function __toString(): string
    {
        return 'onesignal';
    }
}
