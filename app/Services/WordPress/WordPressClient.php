<?php

namespace App\Services\WordPress;

use App\Exceptions\WordPressUnavailableException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Unico punto che conosce la REST del plugin WordPress (proxy live + bridge HMAC).
 * Il resto del backend ignora i dettagli REST/firma. Testabile con Http::fake().
 */
class WordPressClient
{
    public function __construct(private HmacSigner $signer) {}

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
            $response = $this->http()->get('/articles', [
                'page'     => $params['page'] ?? 1,
                'per_page' => $params['per_page'] ?? 10,
                'search'   => $params['search'] ?? '',
            ]);
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

    /** Bridge di verifica account WP (firmato HMAC). Null se l'account non esiste. */
    public function verifyAccount(string $identifier): ?array
    {
        $response = $this->signedPost('/verify-account', ['identifier' => $identifier]);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new WordPressUnavailableException('verify-account ha risposto ' . $response->status());
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
     * POST firmato: il body grezzo inviato è esattamente quello firmato (così il
     * plugin può verificare hash_hmac sul body ricevuto).
     */
    private function signedPost(string $uri, array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $this->signer->sign($body);

        try {
            return $this->http()
                ->withHeaders(['X-SNAPP-Signature' => $signature])
                ->withBody($body, 'application/json')
                ->post($uri);
        } catch (Throwable $e) {
            throw new WordPressUnavailableException('WordPress non raggiungibile.', 0, $e);
        }
    }
}
