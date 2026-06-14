<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OrgChartMember */
class OrgChartMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'photo' => $this->photoUrl(),
            'email' => $this->email,
            // Albero: figli ricorsivi (eager-loaded dal controller).
            'children' => self::collection($this->whenLoaded('childrenRecursive')),
        ];
    }
}
