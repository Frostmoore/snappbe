<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SocialLink
 */
class SocialLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'label' => $this->label,
            'url' => $this->url,
            'icon' => $this->iconUrl(),
            'is_svg' => $this->isSvg(),
            'background_color' => $this->background_color,
            'icon_color' => $this->icon_color,
        ];
    }
}
