<?php

namespace App\Services\WordPress;

use Illuminate\Support\Facades\Cache;

/**
 * Cache breve degli eventi in proxy live dal sito WP, con versionamento.
 *
 * Stessa strategia di {@see ArticleCache}: lo store DB non supporta i tag, quindi
 * una "versione" nella key permette di invalidare tutte le liste in un colpo
 * (es. quando una pagina-evento viene pubblicata/modificata, via webhook).
 */
class EventCache
{
    private const VERSION_KEY = 'snapp:wp:events:ver';
    public const TTL_SECONDS = 120;

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    /** Invalida tutte le liste/eventi in cache. */
    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function key(string $suffix): string
    {
        return 'snapp:wp:events:v' . self::version() . ':' . $suffix;
    }
}
