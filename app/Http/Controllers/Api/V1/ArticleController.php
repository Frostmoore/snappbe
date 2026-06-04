<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\WordPressUnavailableException;
use App\Http\Resources\ArticleResource;
use App\Http\Responses\ApiResponse;
use App\Services\WordPress\ArticleCache;
use App\Services\WordPress\WordPressClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Proxy live degli articoli WordPress (D7). L'app vede `/api/v1/articles` come
 * qualsiasi risorsa; la sorgente WP è invisibile. Cache breve + 503 se WP è giù.
 */
class ArticleController extends Controller
{
    public function __construct(private WordPressClient $client) {}

    public function index(Request $request): JsonResponse
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));
        $search  = trim((string) $request->query('search', ''));

        $key = ArticleCache::key("index:p{$page}:pp{$perPage}:s" . md5($search));

        try {
            $data = Cache::remember($key, ArticleCache::TTL_SECONDS, fn () => $this->client->articles([
                'page'     => $page,
                'per_page' => $perPage,
                'search'   => $search,
            ]));
        } catch (WordPressUnavailableException $e) {
            return ApiResponse::error('Servizio articoli non disponibile.', status: 503);
        }

        return ApiResponse::ok(
            ArticleResource::collection($data['items']),
            [
                'total'       => $data['total'],
                'total_pages' => $data['total_pages'],
                'page'        => $page,
                'per_page'    => $perPage,
            ]
        );
    }

    public function show(int $id): JsonResponse
    {
        $key = ArticleCache::key("show:{$id}");

        try {
            $article = Cache::remember($key, ArticleCache::TTL_SECONDS, fn () => $this->client->article($id));
        } catch (WordPressUnavailableException $e) {
            return ApiResponse::error('Servizio articoli non disponibile.', status: 503);
        }

        if (! $article) {
            return ApiResponse::error('Articolo non trovato.', status: 404);
        }

        return ApiResponse::ok(new ArticleResource($article));
    }
}
