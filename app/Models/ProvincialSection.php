<?php

namespace App\Models;

use App\Models\Concerns\HasSectionDefaults;
use Illuminate\Database\Eloquent\Model;

class ProvincialSection extends Model
{
    use HasSectionDefaults;

    protected $fillable = [
        'name', 'text_bold', 'text_italic', 'province', 'region', 'address', 'email', 'phone', 'website', 'notes',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
