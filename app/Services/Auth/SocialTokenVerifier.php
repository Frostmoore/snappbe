<?php

namespace App\Services\Auth;

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
     * Verifica l'identity token Apple.
     *
     * TODO (fase 2): implementare la verifica del JWT Apple contro le chiavi
     * pubbliche (https://appleid.apple.com/auth/keys), controllando iss
     * (https://appleid.apple.com) e aud ∈ { bundle id iOS, Services ID Android }.
     * Richiede le credenziali dell'Apple Developer Program.
     */
    public function apple(string $identityToken): ?array
    {
        return null;
    }
}
