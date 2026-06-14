<?php

namespace App\Console\Commands;

use App\Services\WordPress\WordPressClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Elenca i ruoli registrati sul sito WordPress (via endpoint plugin /roles, HMAC).
 * Utile per impostare la mappatura ruolo WP → ruolo app.
 */
class ListWpRoles extends Command
{
    protected $signature = 'snapp:wp-roles';

    protected $description = 'Elenca i ruoli definiti sul sito WordPress collegato';

    public function handle(WordPressClient $client): int
    {
        try {
            $roles = $client->roles();
        } catch (Throwable $e) {
            $this->error('Impossibile leggere i ruoli da WordPress: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (empty($roles)) {
            $this->warn('Nessun ruolo restituito.');

            return self::SUCCESS;
        }

        $this->table(
            ['Key (sito)', 'Nome', 'Utenti'],
            collect($roles)->map(fn ($r) => [
                $r['key'] ?? '',
                $r['name'] ?? '',
                $r['users'] ?? 0,
            ])->all()
        );

        $this->info(count($roles) . ' ruoli trovati su ' . config('snapp.wordpress.base_url'));

        return self::SUCCESS;
    }
}
