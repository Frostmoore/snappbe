<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Visibilità per ruolo WP: `visible_roles` (array di slug). Vuoto/null = tutti.
 */
trait HasVisibleRoles
{
    public function scopeVisibleToRole(Builder $query, ?string $role): Builder
    {
        return $query->where(function (Builder $q) use ($role) {
            $q->whereNull('visible_roles')->orWhereJsonLength('visible_roles', 0);
            if ($role !== null && $role !== '') {
                $q->orWhereJsonContains('visible_roles', $role);
            }
        });
    }

    public function isVisibleToRole(?string $role): bool
    {
        $roles = $this->visible_roles ?? [];

        return empty($roles) || ($role !== null && in_array($role, $roles, true));
    }
}
