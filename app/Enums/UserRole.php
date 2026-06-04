<?php

namespace App\Enums;

/**
 * Ruoli applicativi (governano l'accesso, NON il livello di iscrizione che arriva da WP).
 * Gerarchia: superadmin > admin > staff > member.
 */
enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Staff = 'staff';
    case Member = 'member';

    /** Può accedere al pannello admin Filament (Fase 3). */
    public function isStaffOrAbove(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::Staff], true);
    }

    /** Può compiere azioni amministrative (creare contenuti, inviare notifiche). */
    public function isAdminOrAbove(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Staff => 'Staff',
            self::Member => 'Membro',
        };
    }
}
