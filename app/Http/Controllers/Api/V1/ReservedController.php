<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\ReservedTile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Area riservata costruita dal pannello (Tile → Sezioni → Elementi scaricabili),
 * filtrata per il ruolo WP dell'utente collegato (`users.wp_role`).
 */
class ReservedController extends Controller
{
    /** Griglia di tiles visibili all'utente. */
    public function tiles(Request $request): JsonResponse
    {
        $role = $request->user()->wp_role;

        $tiles = ReservedTile::query()
            ->where('is_active', true)
            ->visibleToRole($role)
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::ok($tiles->map(fn (ReservedTile $t) => [
            'id'       => $t->id,
            'title'    => $t->title,
            'subtitle' => $t->subtitle,
            'icon'     => $t->icon_path ? Storage::disk('public')->url($t->icon_path) : null,
            'color'    => $t->color,
        ]));
    }

    /** Dettaglio tile: sezioni + elementi scaricabili, filtrati per ruolo. */
    public function tile(Request $request, int $tile): JsonResponse
    {
        $role = $request->user()->wp_role;

        $model = ReservedTile::query()
            ->where('is_active', true)
            ->visibleToRole($role)
            ->find($tile);

        if (! $model) {
            return ApiResponse::error('Contenuto non trovato.', status: 404);
        }

        $sections = $model->sections()
            ->visibleToRole($role)
            ->with(['elements' => fn ($q) => $q->visibleToRole($role)->orderBy('sort_order')])
            ->get();

        return ApiResponse::ok([
            'id'       => $model->id,
            'title'    => $model->title,
            'subtitle' => $model->subtitle,
            'sections' => $sections->map(fn ($s) => [
                'id'       => $s->id,
                'title'    => $s->title,
                'elements' => $s->elements->map(fn ($e) => [
                    'id'           => $e->id,
                    'title'        => $e->title,
                    'description'  => $e->description,
                    'download_url' => $e->downloadUrl(),
                ]),
            ]),
        ]);
    }
}
