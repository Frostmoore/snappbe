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
                if (! $wpAccount->wp_user_id) {
                    continue;
                }

                try {
                    // Re-sync senza password: endpoint info autenticato via HMAC,
                    // identificato per wp_user_id (no enumerazione per email).
                    $data = $client->accountInfo((int) $wpAccount->wp_user_id);
                } catch (Throwable $e) {
                    $this->warn("WordPress non raggiungibile per #{$wpAccount->wp_user_id}: " . $e->getMessage());
                    continue;
                }

                if (! $data) {
                    continue;
                }

                $roles    = $data['roles'] ?? [];
                $roleSlug = isset($roles[0]) ? (string) $roles[0] : null;
                $roleLabel = null;
                if ($roleSlug) {
                    try {
                        $roleLabel = $client->rolesMap()[$roleSlug] ?? null;
                    } catch (Throwable $e) {
                        $roleLabel = null;
                    }
                }

                $wpAccount->update([
                    'level'       => $data['level'] ?? null,
                    'level_label' => $data['level_label'] ?? null,
                    'roles'       => $roles,
                    'role'        => $roleSlug,
                    'role_label'  => $roleLabel,
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
