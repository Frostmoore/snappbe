<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\WordPress\ArticleCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Riceve i webhook del plugin WP (firma già verificata dal middleware `wp.signature`).
 *
 * Con le notifiche manuali (D6), qui il webhook serve SOLO a invalidare la cache
 * degli articoli in proxy live: niente push automatica.
 */
class WordPressWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $event    = (string) $request->input('event');
        $wpPostId = (int) $request->input('wp_post_id');

        if ($wpPostId > 0) {
            Cache::forget(ArticleCache::key("show:{$wpPostId}"));
        }

        // Invalida tutte le liste in cache (la versione cambia).
        ArticleCache::bump();

        return ApiResponse::ok(['message' => 'Cache articoli aggiornata.', 'event' => $event]);
    }
}
