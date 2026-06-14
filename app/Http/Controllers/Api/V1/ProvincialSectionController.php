<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ProvincialSectionResource;
use App\Http\Responses\ApiResponse;
use App\Models\ProvincialSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvincialSectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProvincialSection::query()->active()->ordered();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('province', 'like', "%{$search}%");
            });
        }

        if ($province = $request->query('province')) {
            $query->where('province', $province);
        }

        return ApiResponse::ok(ProvincialSectionResource::collection($query->get()));
    }
}
