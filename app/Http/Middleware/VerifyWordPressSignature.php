<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica la firma HMAC dei webhook in arrivo da WordPress (server-to-server).
 * Nessun handler deve fidarsi di una richiesta non firmata. Include anti-replay
 * basato sul timestamp del payload (finestra 5 minuti).
 */
class VerifyWordPressSignature
{
    private const REPLAY_WINDOW = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('snapp.wordpress.hmac_secret');
        if ($secret === '') {
            abort(500, 'HMAC secret non configurato.');
        }

        $signature = $request->header('X-SNAPP-Signature');
        $expected  = hash_hmac('sha256', $request->getContent(), $secret);

        if (! $signature || ! hash_equals($expected, $signature)) {
            abort(401, 'Firma non valida.');
        }

        $timestamp = (int) $request->input('timestamp');
        if ($timestamp > 0 && abs(time() - $timestamp) > self::REPLAY_WINDOW) {
            abort(401, 'Richiesta scaduta.');
        }

        return $next($request);
    }
}
