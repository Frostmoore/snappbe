<?php

namespace App\Services\WordPress;

/**
 * Firma HMAC-SHA256 del body grezzo, condivisa con il plugin WordPress.
 * Il segreto è `snapp.wordpress.hmac_secret` (= secret del plugin).
 */
class HmacSigner
{
    public function sign(string $body): string
    {
        return hash_hmac('sha256', $body, (string) config('snapp.wordpress.hmac_secret'));
    }
}
