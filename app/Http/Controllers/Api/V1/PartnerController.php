<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PartnerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Partner::query()->active()->ordered();

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        return ApiResponse::ok(PartnerResource::collection($query->get()));
    }
}
