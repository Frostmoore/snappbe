<?php

namespace App\Services\WordPress;

use Illuminate\Support\Facades\Cache;

/**
 * Cache breve degli articoli in proxy live, con versionamento.
 *
 * Lo store di default (database) non supporta i tag, quindi per invalidare TUTTE
 * le liste in un colpo (es. su webhook WP) si usa una "versione" inclusa nella key:
 * bumpando la versione, tutte le vecchie key diventano irraggiungibili.
 */
class ArticleCache
{
    private const VERSION_KEY = 'snapp:wp:articles:ver';
    public const TTL_SECONDS = 120;

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    /** Invalida tutte le liste/articoli in cache. */
    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function key(string $suffix): string
    {
        return 'snapp:wp:articles:v' . self::version() . ':' . $suffix;
    }
}
