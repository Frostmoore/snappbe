<?php

namespace App\Enums;

enum PartnerType: string
{
    case Convenzione = 'convenzione';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Convenzione => 'Convenzione',
            self::Partner => 'Partner',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
