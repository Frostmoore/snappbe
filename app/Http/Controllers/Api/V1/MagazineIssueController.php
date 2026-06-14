<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\MagazineIssueResource;
use App\Http\Responses\ApiResponse;
use App\Models\MagazineIssue;
use Illuminate\Http\JsonResponse;

class MagazineIssueController extends Controller
{
    public function index(): JsonResponse
    {
        $issues = MagazineIssue::query()->active()->ordered()->get();

        return ApiResponse::ok(MagazineIssueResource::collection($issues));
    }
}
