<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AppSettings
 */
class AppSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'app_name' => $this->app_name,
            'header_image' => $this->headerImageUrl(),
            'header_video' => $this->headerVideoUrl(),
            'logo' => $this->logoUrl(),
            'primary_color' => $this->primary_color,
        ];
    }
}
