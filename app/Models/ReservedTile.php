<?php

namespace App\Models;

use App\Models\Concerns\HasVisibleRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservedTile extends Model
{
    use HasVisibleRoles;

    protected $fillable = ['title', 'subtitle', 'icon_path', 'color', 'sort_order', 'is_active', 'visible_roles'];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'visible_roles' => 'array',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ReservedSection::class)->orderBy('sort_order');
    }
}
