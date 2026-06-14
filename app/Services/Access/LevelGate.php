<?php

namespace App\Services\Access;

use App\Models\AccessLevel;
use App\Models\User;

/**
 * Regola unica di visibilità per livello: un contenuto con `min_level` è visibile
 * se l'utente ha un livello con peso >= a quello richiesto. Anonimi = solo pubblico.
 */
class LevelGate
{
    public function canSee(?User $user, ?string $minLevel): bool
    {
        if ($minLevel === null || $minLevel === '' || $minLevel === 'public') {
            return true;
        }

        $required = AccessLevel::weightFor($minLevel);
        if ($required <= 0) {
            return true;
        }

        if (! $user || ! $user->membership_level) {
            return false;
        }

        return AccessLevel::weightFor($user->membership_level) >= $required;
    }
}
