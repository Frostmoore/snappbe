<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Convenzione comune alle entità "sezione app": attive/ordinate.
 * Richiede le colonne `is_active` (bool) e `sort_order` (int).
 */
trait HasSectionDefaults
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
