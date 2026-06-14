<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AppSettingsResource;
use App\Http\Resources\HomeSectionResource;
use App\Http\Resources\SocialLinkResource;
use App\Models\AppSettings;
use App\Models\HomeSection;
use App\Models\SocialLink;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Dati globali dell'app: impostazioni (header/logo) e social links.
 */
class AppController extends Controller
{
    public function settings(): JsonResponse
    {
        return ApiResponse::ok(new AppSettingsResource(AppSettings::current()));
    }

    public function socialLinks(): JsonResponse
    {
        $links = SocialLink::query()->active()->ordered()->get();

        return ApiResponse::ok(SocialLinkResource::collection($links));
    }

    public function sections(): JsonResponse
    {
        $sections = HomeSection::query()->active()->ordered()->get();

        return ApiResponse::ok(HomeSectionResource::collection($sections));
    }
}
