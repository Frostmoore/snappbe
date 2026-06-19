<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Normalizza un evento WP (array dal proxy) nel formato app. L'evento è una
 * PAGINA del sito marcata "È un evento": `registration_url` = link della pagina,
 * usato in app per il bottone "Registrati all'evento".
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this['id'] ?? null,
            'title'            => $this['title'] ?? null,
            'slug'             => $this['slug'] ?? null,
            'description'      => $this['description'] ?? null,
            'cover'            => $this['image'] ?? null,
            'location'         => $this['address'] ?? null,
            'region'           => $this['region'] ?? null,
            'province'         => $this['province'] ?? null,
            'type'             => $this['type'] ?? null,
            'type_label'       => $this['type_label'] ?? null,
            'starts_at'        => $this['starts_at'] ?? null,
            'ends_at'          => null,
            'registration_url' => $this['link'] ?? null,
        ];
    }
}
