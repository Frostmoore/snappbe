<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\DocumentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Document;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function index(): JsonResponse
    {
        $documents = Document::query()->active()->ordered()->get();

        return ApiResponse::ok(DocumentResource::collection($documents));
    }
}
