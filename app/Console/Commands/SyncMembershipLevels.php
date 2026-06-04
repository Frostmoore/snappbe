<?php

namespace App\Console\Commands;

use App\Models\WpAccount;
use App\Services\WordPress\AccountLinker;
use App\Services\WordPress\WordPressClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Riallinea i livelli di iscrizione dagli account WordPress collegati.
 * Il livello su WP può cambiare (rinnovo/scadenza iscrizione). Schedulato giornaliero.
 */
class SyncMembershipLevels extends Command
{
    protected $signature = 'snapp:sync-levels';

    protected $description = 'Riallinea i livelli di iscrizione dagli account WordPress collegati';

    public function handle(WordPressClient $client, AccountLinker $linker): int
    {
        $synced = 0;

        WpAccount::query()->whereHas('links')->with('links.user')->chunkById(100, function ($accounts) use ($client, $linker, &$synced) {
            foreach ($accounts as $wpAccount) {
                $identifier = $wpAccount->email ?: $wpAccount->username;
                if (! $identifier) {
                    continue;
                }

                try {
                    $data = $client->verifyAccount($identifier);
                } catch (Throwable $e) {
                    $this->warn("WordPress non raggiungibile per {$identifier}: " . $e->getMessage());
                    continue;
                }

                if (! $data) {
                    continue;
                }

                $wpAccount->update([
                    'level'       => $data['level'] ?? null,
                    'level_label' => $data['level_label'] ?? null,
                    'roles'       => $data['roles'] ?? [],
                    'synced_at'   => now(),
                ]);

                foreach ($wpAccount->links as $link) {
                    if ($link->user) {
                        $linker->applyLevel($link->user, $wpAccount);
                    }
                }

                $synced++;
            }
        });

        $this->info("Livelli sincronizzati: {$synced}");

        return self::SUCCESS;
    }
}
