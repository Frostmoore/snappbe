<?php

namespace App\Models;

use App\Models\Concerns\HasSectionDefaults;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SocialLink extends Model
{
    use HasSectionDefaults;

    protected $fillable = [
        'platform',
        'label',
        'icon_path',
        'background_color',
        'icon_color',
        'url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function iconUrl(): ?string
    {
        return $this->icon_path ? Storage::disk('public')->url($this->icon_path) : null;
    }

    public function isSvg(): bool
    {
        return $this->icon_path !== null && Str::endsWith(Str::lower($this->icon_path), '.svg');
    }
}
