<?php

namespace App\Models;

use App\Models\Concerns\HasSectionDefaults;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MagazineIssue extends Model
{
    use HasSectionDefaults;

    protected $fillable = [
        'title', 'number', 'cover_path', 'url', 'issue_date', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'issue_date' => 'date',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }
}
