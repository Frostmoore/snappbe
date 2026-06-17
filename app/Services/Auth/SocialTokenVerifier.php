<?php

namespace App\Services\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Verifica i token social ricevuti dall'app e restituisce un'identità normalizzata.
 *
 * Sicurezza: NON ci si limita a "decodificare" il token. Per Google si verifica
 * l'id_token tramite l'endpoint ufficiale (firma + scadenza controllate da Google)
 * e — soprattutto — si controlla che l'**audience** coincida col nostro Web Client ID.
 * Senza questo controllo un access/id token emesso per un'altra app potrebbe essere
 * riusato per impersonare l'utente (token substitution).
 */
class SocialTokenVerifier
{
    /**
     * Verifica un id_token Google.
     *
     * @return array{id:string,email:?string,name:?string}|null  null = token non valido
     */
    public function google(string $idToken): ?array
    {
        $expectedAud = (string) config('snapp.oauth.google.client_id');
        if ($expectedAud === '' || trim($idToken) === '') {
            return null;
        }

        try {
            $resp = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $resp->ok()) {
            return null;
        }

        $claims = $resp->json();
        if (! is_array($claims)) {
            return null;
        }

        // L'audience DEVE essere il nostro Web Client ID (server client id).
        if (($claims['aud'] ?? null) !== $expectedAud) {
            return null;
        }

        // Issuer atteso di Google.
        $iss = (string) ($claims['iss'] ?? '');
        if (! in_array($iss, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            return null;
        }

        // L'endpoint tokeninfo rifiuta i token scaduti, ma ricontrolliamo comunque.
        if (isset($claims['exp']) && (int) $claims['exp'] < time()) {
            return null;
        }

        $sub = (string) ($claims['sub'] ?? '');
        if ($sub === '') {
            return null;
        }

        return [
            'id'    => $sub,
            'email' => isset($claims['email']) ? (string) $claims['email'] : null,
            'name'  => isset($claims['name']) ? (string) $claims['name'] : null,
        ];
    }

    /**
     * Verifica l'identity token Apple (JWT) contro le chiavi pubbliche Apple.
     *
     * Controlla firma (RS256, chiavi da appleid.apple.com/auth/keys), issuer
     * (https://appleid.apple.com) e audience ∈ { bundle id iOS, Services ID
     * Android }. Non serve la chiave privata `.p8` (solo verifica, non scambio
     * del codice).
     *
     * @return array{id:string,email:?string,name:?string}|null  null = token non valido
     */
    public function apple(string $identityToken): ?array
    {
        $token = trim($identityToken);
        if ($token === '') {
            return null;
        }

        $allowedAud = array_values(array_filter([
            (string) config('snapp.oauth.apple.client_id'),   // bundle id (iOS)
            (string) config('snapp.oauth.apple.services_id'), // Services ID (Android/web)
        ]));
        if ($allowedAud === []) {
            return null;
        }

        // Decodifica + verifica firma. Se la kid non è tra le chiavi in cache
        // (rotazione), ricarica una volta le chiavi e riprova.
        $claims = $this->decodeApple($token, false);
        if ($claims === null) {
            $claims = $this->decodeApple($token, true);
        }
        if ($claims === null) {
            return null;
        }

        if (($claims['iss'] ?? '') !== 'https://appleid.apple.com') {
            return null;
        }
        if (! in_array($claims['aud'] ?? '', $allowedAud, true)) {
            return null;
        }
        if (isset($claims['exp']) && (int) $claims['exp'] < time()) {
            return null;
        }

        $sub = (string) ($claims['sub'] ?? '');
        if ($sub === '') {
            return null;
        }

        // Apple invia l'email nel token (verificata); il NOME non è nel token,
        // arriva separatamente solo al primissimo consenso → lo lasciamo nullo.
        return [
            'id'    => $sub,
            'email' => isset($claims['email']) ? (string) $claims['email'] : null,
            'name'  => null,
        ];
    }

    /**
     * Decodifica e verifica un token Apple con le chiavi pubbliche (in cache 1h).
     *
     * @param  bool  $forceRefresh  ricarica le chiavi ignorando la cache
     * @return array<string,mixed>|null
     */
    private function decodeApple(string $token, bool $forceRefresh): ?array
    {
        $keys = $this->appleJwks($forceRefresh);
        if ($keys === null) {
            return null;
        }

        try {
            $decoded = JWT::decode($token, JWK::parseKeySet($keys));
        } catch (\Throwable $e) {
            return null;
        }

        return (array) $decoded;
    }

    /**
     * Set di chiavi pubbliche Apple (JWKS), cache 1h. Non cache-a i fallimenti.
     *
     * @return array<string,mixed>|null
     */
    private function appleJwks(bool $forceRefresh): ?array
    {
        if (! $forceRefresh) {
            $cached = Cache::get('apple_jwks');
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $resp = Http::timeout(8)->get('https://appleid.apple.com/auth/keys');
        } catch (\Throwable $e) {
            return null;
        }
        if (! $resp->ok()) {
            return null;
        }

        $keys = $resp->json();
        if (! is_array($keys) || ! isset($keys['keys'])) {
            return null;
        }

        Cache::put('apple_jwks', $keys, 3600);

        return $keys;
    }
}
