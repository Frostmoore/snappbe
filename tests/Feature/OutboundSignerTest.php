<?php

namespace Tests\Feature;

use App\Services\WordPress\OutboundSigner;
use Tests\TestCase;

class OutboundSignerTest extends TestCase
{
    public function test_uses_ed25519_when_signing_key_configured(): void
    {
        if (! function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('sodium non disponibile');
        }

        $keypair = sodium_crypto_sign_keypair();
        config(['snapp.wordpress.signing_secret_key' => base64_encode(sodium_crypto_sign_secretkey($keypair))]);
        $publicKey = sodium_crypto_sign_publickey($keypair);

        $body    = '{"identifier":"mario@sna.it"}';
        $headers = (new OutboundSigner())->headers($body);

        $this->assertSame('ed25519', $headers['X-SNAPP-Signature-Alg']);
        $this->assertArrayHasKey('X-SNAPP-Timestamp', $headers);

        // WordPress verificherebbe ESATTAMENTE così, con la sola chiave pubblica.
        $message = $headers['X-SNAPP-Timestamp'] . "\n" . $body;
        $this->assertTrue(
            sodium_crypto_sign_verify_detached(base64_decode($headers['X-SNAPP-Signature']), $message, $publicKey)
        );
    }

    public function test_falls_back_to_hmac_without_signing_key(): void
    {
        config([
            'snapp.wordpress.signing_secret_key' => '',
            'snapp.wordpress.hmac_secret'        => 'shared-secret',
        ]);

        $body    = '{"identifier":"mario@sna.it"}';
        $headers = (new OutboundSigner())->headers($body);

        $this->assertArrayNotHasKey('X-SNAPP-Signature-Alg', $headers);
        $this->assertSame(hash_hmac('sha256', $body, 'shared-secret'), $headers['X-SNAPP-Signature']);
    }
}
