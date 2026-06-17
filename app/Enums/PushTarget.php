<?php

namespace App\Enums;

/** Destinatari di una notifica push. */
enum PushTarget: string
{
    case All = 'all';        // tutti i device
    case Level = 'level';    // utenti con livello >= target_level
    case Role = 'role';      // utenti con un ruolo WordPress (target_role)
    case Users = 'users';    // utenti specifici

    public function label(): string
    {
        return match ($this) {
            self::All => 'Tutti',
            self::Level => 'Per livello',
            self::Role => 'Per ruolo WP',
            self::Users => 'Utenti specifici',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
