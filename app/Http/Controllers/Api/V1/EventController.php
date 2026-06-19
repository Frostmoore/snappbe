<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\WordPressUnavailableException;
use App\Http\Resources\EventResource;
use App\Http\Responses\ApiResponse;
use App\Services\WordPress\EventCache;
use App\Services\WordPress\WordPressClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Proxy live degli eventi WordPress. Gli eventi sono PAGINE del sito marcate
 * "È un evento" dal plugin (scheda Evento SNAPP): l'app vede `/api/v1/events`
 * come qualsiasi risorsa, la sorgente WP è invisibile. Cache breve + 503 se WP è giù.
 */
class EventController extends Controller
{
    public function __construct(private WordPressClient $client) {}

    public function index(Request $request): JsonResponse
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $key    = EventCache::key("index:p{$page}:pp{$perPage}");
        $params = ['page' => $page, 'per_page' => $perPage];

        try {
            // Pull-to-refresh (`?refresh=1`): bypassa la cache e la ripopola con i
            // dati freschi dal sito, così l'utente vede subito le modifiche.
            if ($request->boolean('refresh')) {
                $data = $this->client->events($params);
                Cache::put($key, $data, EventCache::TTL_SECONDS);
            } else {
                $data = Cache::remember($key, EventCache::TTL_SECONDS, fn () => $this->client->events($params));
            }
        } catch (WordPressUnavailableException $e) {
            return ApiResponse::error('Servizio eventi non disponibile.', status: 503);
        }

        return ApiResponse::ok(
            EventResource::collection($data['items']),
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
        $key = EventCache::key("show:{$id}");

        try {
            $event = Cache::remember($key, EventCache::TTL_SECONDS, fn () => $this->client->event($id));
        } catch (WordPressUnavailableException $e) {
            return ApiResponse::error('Servizio eventi non disponibile.', status: 503);
        }

        if (! $event) {
            return ApiResponse::error('Evento non trovato.', status: 404);
        }

        return ApiResponse::ok(new EventResource($event));
    }
}
