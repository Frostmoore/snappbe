<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bozza',
            self::Published => 'Pubblicato',
            self::Archived => 'Archiviato',
        };
    }

    /** @return array<string, string> per i Select Filament (value => label) */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
