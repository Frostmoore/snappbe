<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Normalizza un articolo WP (array dal proxy) nel formato app.
 * Chiavi allineate dove sensato a PostResource, così l'app ha un modello unico.
 */
class ArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this['id'] ?? null,
            'type'         => 'article',
            'title'        => $this['title'] ?? null,
            'excerpt'      => $this['excerpt'] ?? null,
            'slug'         => $this['slug'] ?? null,
            'link'         => $this['link'] ?? null,
            'author'       => $this['author'] ?? null,
            'image'        => $this['image'] ?? null,
            'categories'   => $this['categories'] ?? [],
            'content'      => $this['content'] ?? null,
            'published_at' => $this['published_at'] ?? null,
            'updated_at'   => $this['updated_at'] ?? null,
        ];
    }
}
