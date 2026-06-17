<?php

namespace App\Services\Push;

use App\Notifications\Push\PushMessage;
use App\Notifications\Push\PushResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper di basso livello sull'API REST di OneSignal (Create Notification).
 *
 * Riusabile: costruisce il payload da un PushMessage (transport-agnostico) e ci
 * applica il targeting passato dal transport. Se le credenziali non sono
 * configurate, NON invia: logga e ritorna un esito di fallimento (così il flusso
 * non crasha in dev / prima di avere le chiavi).
 *
 * @see https://documentation.onesignal.com/reference/create-notification
 */
class OneSignalClient
{
    public function isConfigured(): bool
    {
        return $this->appId() !== '' && $this->restApiKey() !== '';
    }

    /**
     * Invia una notifica con il targeting indicato (include_aliases / included_segments).
     *
     * @param array<string,mixed> $targeting
     */
    public function send(PushMessage $message, array $targeting): PushResult
    {
        if (! $this->isConfigured()) {
            Log::warning('OneSignal non configurato: push non inviata.', [
                'title' => $message->title,
                'targeting' => array_keys($targeting),
            ]);

            return new PushResult(failure: 1);
        }

        $payload = array_merge([
            'app_id'   => $this->appId(),
            // OneSignal richiede la chiave lingua di default "en".
            'headings' => ['en' => $message->title],
            'contents' => ['en' => $message->body],
            'data'     => $message->dataPayload(),
        ], $targeting);

        if ($message->image !== null && $message->image !== '') {
            $payload['big_picture'] = $message->image;            // Android
            $payload['ios_attachments'] = ['snapp_img' => $message->image]; // iOS
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic '.$this->restApiKey(),
                'Content-Type'  => 'application/json; charset=utf-8',
            ])->timeout(15)->post($this->apiUrl(), $payload);
        } catch (\Throwable $e) {
            Log::error('OneSignal: errore di rete sull\'invio push.', ['error' => $e->getMessage()]);

            return new PushResult(failure: 1);
        }

        $body = $response->json();

        // OneSignal risponde 200 con { id, recipients } oppure con { errors }.
        if (! $response->successful() || ! is_array($body) || isset($body['errors'])) {
            Log::error('OneSignal: invio push rifiutato.', [
                'status' => $response->status(),
                'body'   => $body,
            ]);

            return new PushResult(failure: 1);
        }

        $recipients = (int) ($body['recipients'] ?? 0);

        // Accettata: contiamo i destinatari (almeno 1 se OneSignal ha accettato).
        return new PushResult(success: max($recipients, 1));
    }

    private function appId(): string
    {
        return trim((string) config('snapp.onesignal.app_id'));
    }

    private function restApiKey(): string
    {
        return trim((string) config('snapp.onesignal.rest_api_key'));
    }

    private function apiUrl(): string
    {
        return (string) config('snapp.onesignal.api_url', 'https://onesignal.com/api/v1/notifications');
    }
}
