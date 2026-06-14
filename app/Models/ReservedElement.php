<?php

namespace App\Models;

use App\Models\Concerns\HasVisibleRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReservedElement extends Model
{
    use HasVisibleRoles;

    protected $fillable = ['reserved_section_id', 'title', 'description', 'file_path', 'external_url', 'sort_order', 'visible_roles'];

    protected function casts(): array
    {
        return ['visible_roles' => 'array'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ReservedSection::class, 'reserved_section_id');
    }

    /** URL da cui scaricare/aprire l'elemento (file caricato o link esterno). */
    public function downloadUrl(): ?string
    {
        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        return $this->external_url ?: null;
    }
}
