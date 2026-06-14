<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\HomeSection
 */
class HomeSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'route' => $this->route,
            'layout' => $this->layout,
            'icon' => $this->iconUrl(),
            'is_svg' => $this->isSvg(),
            'background_color' => $this->background_color,
            'icon_color' => $this->icon_color,
        ];
    }
}
