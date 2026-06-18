<?php

namespace App\Services\WordPress;

use App\Notifications\Auth\SnaPasswordResetCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/**
 * Reset password del SITO SNA via codice email, completato IN-APP (no sito web).
 *
 * Flusso: l'utente inserisce un'email → se esiste su SNA, gli inviamo un codice
 * a 6 cifre; inserendo codice + nuova password nell'app, verifichiamo il codice
 * (prova del possesso dell'email = dell'account) e impostiamo la nuova password
 * su WP tramite l'endpoint firmato `/set-password`.
 */
class SnaPasswordResetService
{
    private const TTL_MINUTES = 30;

    public function __construct(private WordPressClient $client) {}

    /** Genera e invia un codice di reset, SOLO se l'email esiste su SNA. */
    public function request(string $email): void
    {
        $email = mb_strtolower(trim($email));

        $account = $this->client->accountByEmail($email);
        if (! $account || empty($account['wp_user_id'])) {
            return; // anti-enumeration: non riveliamo se l'email esiste o no
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('sna_password_resets')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'wp_user_id' => (int) $account['wp_user_id'], 'created_at' => now()],
        );

        Notification::route('mail', $email)->notify(new SnaPasswordResetCode($code));
    }

    /**
     * Verifica SOLO che il codice sia valido e non scaduto, senza consumarlo
     * (step OTP separato, prima della nuova password). Il codice resta valido
     * per il successivo `reset()`.
     */
    public function verifyCode(string $email, string $code): bool
    {
        return $this->validRow(mb_strtolower(trim($email)), $code) !== null;
    }

    /**
     * Verifica il codice e imposta la nuova password sul sito SNA.
     *
     * @return bool true se reimpostata; false se codice non valido/scaduto.
     */
    public function reset(string $email, string $code, string $newPassword): bool
    {
        $email = mb_strtolower(trim($email));

        $row = $this->validRow($email, $code);
        if (! $row) {
            return false;
        }

        $ok = $this->client->setPassword((int) $row->wp_user_id, $newPassword);

        DB::table('sna_password_resets')->where('email', $email)->delete();

        return $ok;
    }

    /**
     * Riga di reset valida (esiste, non scaduta, codice corretto) o null.
     * Cancella la riga se scaduta. NON la consuma se valida.
     */
    private function validRow(string $email, string $code): ?object
    {
        $row = DB::table('sna_password_resets')->where('email', $email)->first();
        if (! $row) {
            return null;
        }

        if (Carbon::parse($row->created_at)->diffInMinutes(now()) > self::TTL_MINUTES) {
            DB::table('sna_password_resets')->where('email', $email)->delete();

            return null;
        }

        return Hash::check($code, $row->token) ? $row : null;
    }
}
