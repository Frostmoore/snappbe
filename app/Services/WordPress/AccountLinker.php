<?php

namespace App\Services\WordPress;

use App\Enums\AccountLinkStatus;
use App\Exceptions\InvalidWpCredentialsException;
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

    public function link(User $user, string $identifier, string $password): AccountLink
    {
        return $this->linkWithData($user, $this->verifyOrFail($identifier, $password));
    }

    /**
     * Dati dell'account WP che combacia con l'email (verificata) dell'utente, o
     * null se non esiste. Serve a PROPORRE il collegamento automatico.
     *
     * @return array<string,mixed>|null
     */
    public function findByUserEmail(User $user): ?array
    {
        return $this->client->accountByEmail($user->email);
    }

    /**
     * Collega per MATCH EMAIL: l'email dell'utente app è già verificata
     * (Google/Apple o verifica email) → è prova di possesso, non serve la password
     * del sito. Null se nessun account WP ha quell'email.
     */
    public function linkByEmail(User $user): ?AccountLink
    {
        $data = $this->client->accountByEmail($user->email);
        if (! $data) {
            return null;
        }

        return $this->linkWithData($user, $data);
    }

    /**
     * Verifica le credenziali contro WP (bridge HMAC) o lancia ValidationException
     * coerente: password errata → campo `password`; account inesistente → `identifier`.
     *
     * @return array<string,mixed> dati account WP normalizzati
     */
    public function verifyOrFail(string $identifier, string $password): array
    {
        try {
            $data = $this->client->verifyAccount($identifier, $password);
        } catch (InvalidWpCredentialsException $e) {
            $host = parse_url((string) config('snapp.wordpress.base_url'), PHP_URL_HOST) ?: 'snaservice.it';

            throw ValidationException::withMessages([
                'password' => ["Password errata. Controlla le credenziali del tuo account su {$host}."],
            ]);
        }

        if (! $data) {
            $host = parse_url((string) config('snapp.wordpress.base_url'), PHP_URL_HOST) ?: 'snaservice.it';

            throw ValidationException::withMessages([
                'identifier' => ["Nessun account trovato su {$host} con questo identificativo."],
            ]);
        }

        return $data;
    }

    /** Crea/aggiorna il WpAccount e il link one-to-one dell'utente, ereditando livello e ruolo. */
    public function linkWithData(User $user, array $data): AccountLink
    {
        [$roleSlug, $roleLabel] = $this->resolveWpRole($data['roles'] ?? []);

        $wpAccount = WpAccount::updateOrCreate(
            ['wp_user_id' => $data['wp_user_id']],
            [
                'username'    => $data['username'] ?? null,
                'email'       => $data['email'] ?? null,
                'level'       => $data['level'] ?? null,
                'level_label' => $data['level_label'] ?? null,
                'roles'       => $data['roles'] ?? [],
                'role'        => $roleSlug,
                'role_label'  => $roleLabel,
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

    /**
     * Propaga livello e RUOLO WP ESATTO (slug + nome) sull'utente.
     * NON tocca `users.role` (permessi app member/staff/admin/superadmin): quello
     * resta separato e protetto. Il ruolo WP serve a differenziare l'area riservata.
     */
    public function applyLevel(User $user, WpAccount $wpAccount): void
    {
        $user->forceFill([
            'membership_level'     => $wpAccount->level, // identity: le key del plugin = key access_levels
            'wp_role'              => $wpAccount->role,
            'wp_role_label'        => $wpAccount->role_label,
            'membership_synced_at' => now(),
        ])->save();
    }

    /**
     * Ruolo WP "primario" (slug + nome ESATTI dal sito). Prende il primo ruolo
     * dell'utente; il nome leggibile è risolto dalla mappa /roles (cache). Se non
     * risolvibile, il nome resta null ma lo slug è comunque esatto.
     *
     * @param array<int,mixed> $wpRoles
     * @return array{0:?string,1:?string} [slug, label]
     */
    private function resolveWpRole(array $wpRoles): array
    {
        $slug = isset($wpRoles[0]) ? (string) $wpRoles[0] : null;
        if (! $slug) {
            return [null, null];
        }

        try {
            $label = $this->client->rolesMap()[$slug] ?? null;
        } catch (\Throwable $e) {
            $label = null; // nome opzionale; lo slug resta esatto
        }

        return [$slug, $label];
    }

    /** Scollega: rimuove il link e azzera il livello ereditato. */
    public function unlink(User $user): void
    {
        $user->accountLink()?->delete();

        $user->forceFill([
            'membership_level'     => null,
            'wp_role'              => null,
            'wp_role_label'        => null,
            'membership_synced_at' => null,
        ])->save();
    }
}
