<?php

namespace App\Enums;

enum PostType: string
{
    case News = 'news';
    case Newsletter = 'newsletter';
    case Tool = 'tool';
    case Generic = 'generic';

    public function label(): string
    {
        return match ($this) {
            self::News => 'News',
            self::Newsletter => 'Newsletter',
            self::Tool => 'Strumento',
            self::Generic => 'Generico',
        };
    }

    /** @return array<string, string> per i Select Filament (value => label) */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
