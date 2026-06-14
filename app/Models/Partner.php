<?php

namespace App\Models;

use App\Enums\PartnerType;
use App\Models\Concerns\HasSectionDefaults;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Partner extends Model
{
    use HasSectionDefaults;

    protected $fillable = [
        'name', 'type', 'logo_path', 'url', 'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['type' => PartnerType::class, 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
