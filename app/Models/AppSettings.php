<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Impostazioni globali dell'app (singleton: una sola riga).
 */
class AppSettings extends Model
{
    protected $fillable = [
        'app_name',
        'header_image_path',
        'header_video_path',
        'logo_path',
        'primary_color',
        'reserved_button_enabled',
    ];

    protected function casts(): array
    {
        return ['reserved_button_enabled' => 'boolean'];
    }

    /** Ritorna (o crea) l'unica riga di impostazioni. */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function headerImageUrl(): ?string
    {
        return $this->header_image_path ? Storage::disk('public')->url($this->header_image_path) : null;
    }

    public function headerVideoUrl(): ?string
    {
        return $this->header_video_path ? Storage::disk('public')->url($this->header_video_path) : null;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
