<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Post */
class PostResource extends JsonResource
{
    /** Includere il corpo completo (solo nel dettaglio). */
    protected bool $withBody = false;

    public function withBody(): static
    {
        $this->withBody = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'slug' => $this->slug,
            'cover' => $this->coverImageUrl(),
            'min_level' => $this->min_level,
            'external_url' => $this->external_url,
            'published_at' => $this->published_at?->toIso8601String(),
            'body' => $this->when($this->withBody, fn () => $this->body),
        ];
    }
}
