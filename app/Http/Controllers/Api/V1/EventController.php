<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\EventResource;
use App\Http\Responses\ApiResponse;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::query()->published()->orderBy('starts_at')->get();

        return ApiResponse::ok(EventResource::collection($events));
    }

    public function show(Event $event): JsonResponse
    {
        if (! $event->is_published) {
            throw new NotFoundHttpException('Evento non trovato.');
        }

        return ApiResponse::ok(new EventResource($event));
    }
}
