<?php

namespace App\Support;

use App\Services\WordPress\WordPressClient;

class WpRoles
{
    /**
     * Opzioni [slug => nome] dei ruoli WP per i select Filament (cache via rolesMap).
     * Vuoto se WordPress è irraggiungibile.
     *
     * @return array<string,string>
     */
    public static function options(): array
    {
        try {
            return app(WordPressClient::class)->rolesMap();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
