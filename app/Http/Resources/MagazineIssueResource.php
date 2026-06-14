<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MagazineIssue */
class MagazineIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'number' => $this->number,
            'cover' => $this->coverUrl(),
            'url' => $this->url,
            'issue_date' => $this->issue_date?->toDateString(),
        ];
    }
}
