<?php

namespace App\Models;

use App\Models\Concerns\HasVisibleRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservedSection extends Model
{
    use HasVisibleRoles;

    protected $fillable = ['reserved_tile_id', 'title', 'sort_order', 'visible_roles'];

    protected function casts(): array
    {
        return ['visible_roles' => 'array'];
    }

    public function tile(): BelongsTo
    {
        return $this->belongsTo(ReservedTile::class, 'reserved_tile_id');
    }

    public function elements(): HasMany
    {
        return $this->hasMany(ReservedElement::class)->orderBy('sort_order');
    }
}
