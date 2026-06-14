<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PostStatus;
use App\Http\Resources\PostResource;
use App\Http\Responses\ApiResponse;
use App\Models\Post;
use App\Services\Access\LevelGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Feed dei post NATIVI in-app, filtrato per livello (anonimo = solo pubblico).
 */
class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));

        $posts = Post::query()
            ->published()
            ->visibleTo($user)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return ApiResponse::ok(
            PostResource::collection($posts->items()),
            [
                'total' => $posts->total(),
                'page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total_pages' => $posts->lastPage(),
            ]
        );
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $user = auth('sanctum')->user();

        if ($post->status !== PostStatus::Published) {
            throw new NotFoundHttpException('Contenuto non trovato.');
        }

        if (! app(LevelGate::class)->canSee($user, $post->min_level)) {
            return ApiResponse::error('Contenuto riservato a un livello superiore.', status: 403);
        }

        return ApiResponse::ok((new PostResource($post))->withBody());
    }
}
