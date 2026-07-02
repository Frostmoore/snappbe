<?php

namespace App\Models;

use App\Models\Concerns\HasSectionDefaults;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Documento scaricabile (caricato dal pannello), esposto alla sezione "Documenti".
 */
class Document extends Model
{
    use HasSectionDefaults;

    protected $fillable = [
        'title', 'description', 'file_path', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }
}
