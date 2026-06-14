<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\OrgChartMemberResource;
use App\Http\Responses\ApiResponse;
use App\Models\OrgChartMember;
use Illuminate\Http\JsonResponse;

class OrgChartController extends Controller
{
    /** Restituisce l'organigramma come albero (vertici + figli ricorsivi). */
    public function index(): JsonResponse
    {
        $roots = OrgChartMember::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->with('childrenRecursive')
            ->get();

        return ApiResponse::ok(OrgChartMemberResource::collection($roots));
    }
}
