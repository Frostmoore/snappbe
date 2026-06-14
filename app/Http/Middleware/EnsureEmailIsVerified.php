<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Variante API di `verified`: l'account NON è attivo finché l'email non è
 * verificata. Il middleware di default reindirizza a una rotta web inesistente
 * in un'app API; qui rispondiamo con un 403 JSON pulito (codice machine-readable
 * `email_unverified`) così l'app può mostrare la schermata di verifica.
 */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user
            || ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail())) {
            return ApiResponse::error(
                'Devi verificare la tua email per usare questa funzione.',
                ['email' => ['email_unverified']],
                403,
            );
        }

        return $next($request);
    }
}
