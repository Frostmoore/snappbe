<?php

namespace App\Services\WordPress;

/**
 * Firma le richieste in uscita verso WordPress (canale server-a-server).
 *
 * Preferisce la firma ASIMMETRICA Ed25519: Laravel possiede la chiave PRIVATA,
 * WordPress soltanto la PUBBLICA (può verificare ma non forgiare). Così, anche
 * se WordPress viene compromesso, non può impersonare Laravel sulle chiamate
 * sensibili (verify-account, account-info, roles, sso-ticket).
 *
 * Se la chiave privata non è configurata, ripiega sull'HMAC legacy (segreto
 * condiviso) per non rompere nulla durante la transizione.
 */
class OutboundSigner
{
    /**
     * Header di firma da allegare alla richiesta (con timestamp anti-replay).
     *
     * @return array<string,string>
     */
    public function headers(string $body): array
    {
        $secretB64 = (string) config('snapp.wordpress.signing_secret_key');

        if ($secretB64 !== '' && function_exists('sodium_crypto_sign_detached')) {
            $timestamp = (string) time();
            $secret    = base64_decode($secretB64, true);
            // Firma "timestamp\nbody": il timestamp è coperto dalla firma (no replay).
            $signature = sodium_crypto_sign_detached($timestamp . "\n" . $body, $secret);

            return [
                'X-SNAPP-Signature'     => base64_encode($signature),
                'X-SNAPP-Timestamp'     => $timestamp,
                'X-SNAPP-Signature-Alg' => 'ed25519',
            ];
        }

        // Legacy: HMAC-SHA256 del body (compatibilità durante la transizione).
        return [
            'X-SNAPP-Signature' => hash_hmac('sha256', $body, (string) config('snapp.wordpress.hmac_secret')),
        ];
    }
}
