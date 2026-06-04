<?php

namespace App\Services\WordPress;

use App\Enums\AccountLinkStatus;
use App\Models\AccountLink;
use App\Models\User;
use App\Models\WpAccount;
use Illuminate\Validation\ValidationException;

/**
 * Collega un utente app al suo account WordPress e fa ereditare il livello.
 *
 * La verifica passa dal bridge HMAC del plugin (`/verify-account`), quindi è
 * autorevole. Un utente app è collegato a UN account WP (one-to-one).
 */
class AccountLinker
{
    public function __construct(private WordPressClient $client) {}

    public function link(User $user, string $identifier): AccountLink
    {
        $data = $this->client->verifyAccount($identifier);

        if (! $data) {
            throw ValidationException::withMessages([
                'identifier' => ['Nessun account SNA trovato con questo identificativo.'],
            ]);
        }

        $wpAccount = WpAccount::updateOrCreate(
            ['wp_user_id' => $data['wp_user_id']],
            [
                'username'    => $data['username'] ?? null,
                'email'       => $data['email'] ?? null,
                'level'       => $data['level'] ?? null,
                'level_label' => $data['level_label'] ?? null,
                'roles'       => $data['roles'] ?? [],
                'synced_at'   => now(),
            ]
        );

        // One-to-one: aggiorna il link esistente dell'utente o ne crea uno nuovo.
        $link = AccountLink::updateOrCreate(
            ['user_id' => $user->id],
            [
                'wp_account_id'       => $wpAccount->id,
                'status'              => AccountLinkStatus::Verified->value,
                'verification_method' => 'wp_bridge',
                'linked_at'           => now(),
            ]
        );

        $this->applyLevel($user, $wpAccount);

        return $link->load('wpAccount');
    }

    /** Propaga il livello dell'account WP sull'utente (cache locale per le query). */
    public function applyLevel(User $user, WpAccount $wpAccount): void
    {
        $user->forceFill([
            'membership_level'     => $wpAccount->level, // identity: le key del plugin = key access_levels
            'membership_synced_at' => now(),
        ])->save();
    }

    /** Scollega: rimuove il link e azzera il livello ereditato. */
    public function unlink(User $user): void
    {
        $user->accountLink()?->delete();

        $user->forceFill([
            'membership_level'     => null,
            'membership_synced_at' => null,
        ])->save();
    }
}
