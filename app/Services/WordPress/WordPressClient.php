<?php

namespace App\Services\WordPress;

use App\Exceptions\InvalidWpCredentialsException;
use App\Exceptions\WordPressUnavailableException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Unico punto che conosce la REST del plugin WordPress (proxy live + bridge HMAC).
 * Il resto del backend ignora i dettagli REST/firma. Testabile con Http::fake().
 */
class WordPressClient
{
    public function __construct(private OutboundSigner $signer) {}

    private function http(): PendingRequest
    {
        $base = rtrim((string) config('snapp.wordpress.base_url'), '/')
            . config('snapp.wordpress.rest_path');

        return Http::baseUrl($base)
            ->timeout((int) config('snapp.wordpress.timeout', 8))
            ->acceptJson();
    }

    /**
     * Elenco articoli normalizzati + metadati di paginazione (header X-WP-*).
     *
     * @return array{items: array<int,array>, total: int, total_pages: int}
     */
    public function articles(array $params): array
    {
        try {
            $response = $this->http()->get('/articles', array_filter([
                'page'     => $params['page'] ?? 1,
                'per_page' => $params['per_page'] ?? 10,
                'search'   => $params['search'] ?? '',
                'category' => $params['category'] ?? null, // slug categoria (es. newsletter)
            ], fn ($v) => $v !== null && $v !== ''));
        } catch (Throwable $e) {
            throw new WordPressUnavailableException('WordPress non raggiungibile.', 0, $e);
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('Errore dal sito WordPress: ' . $response->status());
        }

        return [
            'items'       => $response->json() ?? [],
            'total'       => (int) $response->header('X-WP-Total'),
            'total_pages' => (int) $response->header('X-WP-TotalPages'),
        ];
    }

    /** Singolo articolo; null se 404. */
    public function article(int $id): ?array
    {
        try {
            $response = $this->http()->get('/articles/' . $id);
        } catch (Throwable $e) {
            throw new WordPressUnavailableException('WordPress non raggiungibile.', 0, $e);
        }

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('Errore dal sito WordPress: ' . $response->status());
        }

        return $response->json();
    }

    /**
     * Elenco eventi normalizzati + metadati di paginazione (header X-WP-*).
     * Gli eventi sono PAGINE WP marcate "È un evento" nel plugin.
     *
     * @return array{items: array<int,array>, total: int, total_pages: int}
     */
    public function events(array $params): array
    {
        try {
            $response = $this->http()->get('/events', array_filter([
                'page'     => $params['page'] ?? 1,
                'per_page' => $params['per_page'] ?? 20,
            ], fn ($v) => $v !== null && $v !== ''));
        } catch (Throwable $e) {
            throw new WordPressUnavailableException('WordPress non raggiungibile.', 0, $e);
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('Errore dal sito WordPress: ' . $response->status());
        }

        return [
            'items'       => $response->json() ?? [],
            'total'       => (int) $response->header('X-WP-Total'),
            'total_pages' => (int) $response->header('X-WP-TotalPages'),
        ];
    }

    /** Singolo evento; null se 404. */
    public function event(int $id): ?array
    {
        try {
            $response = $this->http()->get('/events/' . $id);
        } catch (Throwable $e) {
            throw new WordPressUnavailableException('WordPress non raggiungibile.', 0, $e);
        }

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('Errore dal sito WordPress: ' . $response->status());
        }

        return $response->json();
    }

    /**
     * Bridge di verifica account WP (firmato HMAC). Autentica identifier + password.
     * Null se l'account non esiste (404); InvalidWpCredentialsException se la password
     * è errata (401).
     */
    public function verifyAccount(string $identifier, string $password): ?array
    {
        $response = $this->signedPost('/verify-account', [
            'identifier' => $identifier,
            'password'   => $password,
        ]);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->status() === 401) {
            throw new InvalidWpCredentialsException('Credenziali del sito SNA non valide.');
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('verify-account ha risposto ' . $response->status());
        }

        return $response->json();
    }

    /**
     * Info account WP SENZA password (solo HMAC): per il re-sync periodico di
     * livello/ruolo. Identifica per `wp_user_id` (id interno noto solo dopo un
     * link riuscito), così non è possibile enumerare account per email/username.
     * Null se l'account non esiste (404).
     */
    public function accountInfo(int $wpUserId): ?array
    {
        $response = $this->signedPost('/account-info', ['wp_user_id' => $wpUserId]);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('account-info ha risposto ' . $response->status());
        }

        return $response->json();
    }

    /**
     * Info account WP dato un EMAIL, SENZA password (solo firma). Usato per
     * PROPORRE il collegamento automatico quando l'email (verificata) dell'utente
     * app combacia con un account del sito. Va invocato SOLO con l'email
     * dell'utente autenticato. Null se nessun account ha quell'email (404).
     */
    public function accountByEmail(string $email): ?array
    {
        $response = $this->signedPost('/account-by-email', ['email' => $email]);

        if ($response->status() === 404) {
            // Distingue "nessun account con questa email" (il NOSTRO endpoint)
            // da "rotta inesistente" (plugin non ancora aggiornato): in quel caso
            // NON è un no-match definitivo, così non marchiamo l'utente per errore.
            if ((string) ($response->json('code') ?? '') === 'rest_no_route') {
                throw new WordPressUnavailableException('Endpoint account-by-email assente: aggiornare il plugin snapp-connector.');
            }

            return null;
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('account-by-email ha risposto ' . $response->status());
        }

        return $response->json();
    }

    /**
     * Imposta una nuova password per l'account WP indicato (solo firma). Va
     * chiamato SOLO dopo aver verificato il possesso dell'email (codice di reset).
     * True se impostata; false se l'account non esiste (404).
     */
    public function setPassword(int $wpUserId, string $newPassword): bool
    {
        $response = $this->signedPost('/set-password', [
            'wp_user_id'   => $wpUserId,
            'new_password' => $newPassword,
        ]);

        if ($response->status() === 404) {
            return false;
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('set-password ha risposto ' . $response->status());
        }

        return (bool) ($response->json('ok') ?? false);
    }

    /**
     * Elenco dei ruoli registrati sul sito WordPress (firmato HMAC).
     * @return array<int,array{key:string,name:string,users:int}>
     */
    public function roles(): array
    {
        $response = $this->signedPost('/roles', []);

        if ($response->failed()) {
            throw new WordPressUnavailableException('roles ha risposto ' . $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Mappa slug→nome dei ruoli del sito (cache breve: i ruoli cambiano di rado).
     * @return array<string,string>
     */
    public function rolesMap(): array
    {
        return Cache::remember('snapp:wp_roles_map', 600, function () {
            $map = [];
            foreach ($this->roles() as $role) {
                if (! empty($role['key'])) {
                    $map[$role['key']] = $role['name'] ?? $role['key'];
                }
            }

            return $map;
        });
    }

    /**
     * Contenuto di una pagina WP (per slug) renderizzato come l'utente collegato.
     * Null se l'account o la pagina non esistono (404).
     *
     * @return array{slug:string,title:string,url:string,html:string}|null
     */
    public function renderedPage(int $wpUserId, string $slug): ?array
    {
        $response = $this->signedPost('/rendered-page', ['wp_user_id' => $wpUserId, 'slug' => $slug]);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('rendered-page ha risposto ' . $response->status());
        }

        return $response->json();
    }

    /** Genera un ticket SSO per la WebView eventi (firmato HMAC). */
    public function ssoTicket(array $payload): array
    {
        $response = $this->signedPost('/sso-ticket', $payload);

        if ($response->failed()) {
            throw new WordPressUnavailableException('sso-ticket ha risposto ' . $response->status());
        }

        return $response->json();
    }

    /**
     * POST firmato: il body grezzo inviato è esattamente quello su cui si calcola
     * la firma (Ed25519 o HMAC, vedi OutboundSigner), così il plugin la verifica
     * sul body ricevuto.
     */
    private function signedPost(string $uri, array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            return $this->http()
                ->withHeaders($this->signer->headers($body))
                ->withBody($body, 'application/json')
                ->post($uri);
        } catch (Throwable $e) {
            throw new WordPressUnavailableException('WordPress non raggiungibile.', 0, $e);
        }
    }
}
