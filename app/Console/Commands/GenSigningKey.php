<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Genera una coppia di chiavi Ed25519 per firmare il canale Laravel→WordPress.
 * La chiave PRIVATA resta su Laravel (.env); la PUBBLICA va nel plugin WP.
 */
class GenSigningKey extends Command
{
    protected $signature = 'snapp:gen-signing-key';

    protected $description = 'Genera la coppia di chiavi Ed25519 per firmare le richieste verso WordPress';

    public function handle(): int
    {
        if (! function_exists('sodium_crypto_sign_keypair')) {
            $this->error("Estensione 'sodium' non disponibile. Abilitala in php.ini (extension=sodium).");

            return self::FAILURE;
        }

        $keypair = sodium_crypto_sign_keypair();
        $secret  = base64_encode(sodium_crypto_sign_secretkey($keypair));
        $public  = base64_encode(sodium_crypto_sign_publickey($keypair));

        $this->info('Coppia di chiavi Ed25519 generata.');
        $this->newLine();
        $this->line('<options=bold>1) CHIAVE PRIVATA</> → nel file .env di Laravel:');
        $this->line('   SNAPP_SIGNING_SECRET_KEY=' . $secret);
        $this->newLine();
        $this->line('<options=bold>2) CHIAVE PUBBLICA</> → in WordPress: Impostazioni → SNAPP Connector → "Chiave pubblica firma (Ed25519)":');
        $this->line('   ' . $public);
        $this->newLine();
        $this->warn('Ordine consigliato: prima incolla la chiave pubblica su WordPress, POI la privata nel .env, poi riavvia il server. Da quel momento WP accetta solo firme Ed25519 (l\'HMAC resta solo per il webhook).');

        return self::SUCCESS;
    }
}
