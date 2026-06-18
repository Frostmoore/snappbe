<?php

namespace App\Console\Commands;

use App\Services\WordPress\ArticleCache;
use App\Services\WordPress\WordPressClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Tiene "caldi" WP e la cache articoli/newsletter del proxy live.
 *
 * Schedulato ogni minuto (TTL cache 120s): forza il refresh delle liste che
 * l'app carica per prime (articoli e newsletter, pagina 1, per_page 15), così
 * il primo accesso dell'utente NON colpisce mai un WP "freddo" (niente 503/lentezza).
 */
class WarmArticleCache extends Command
{
    protected $signature = 'snapp:warm-articles';

    protected $description = 'Tiene caldi WP e la cache articoli/newsletter (proxy live).';

    public function handle(WordPressClient $client): int
    {
        // Stesse key e parametri del controller + dell'app (per_page 15, pagina 1).
        $targets = [
            [ArticleCache::key('index:p1:pp15:s'.md5('')), ['page' => 1, 'per_page' => 15, 'search' => '']],
            [ArticleCache::key('newsletters:p1:pp15'), ['page' => 1, 'per_page' => 15, 'category' => 'newsletter']],
        ];

        foreach ($targets as [$key, $params]) {
            try {
                $data = $client->articles($params);
                Cache::put($key, $data, ArticleCache::TTL_SECONDS);
            } catch (\Throwable $e) {
                // Un errore WP non deve far fallire il task schedulato.
                $this->warn('warm fallito per '.$key.': '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
