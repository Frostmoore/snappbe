<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\WordPressUnavailableException;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\WordPress\SnaPasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reset password del sito SNA via codice email, completato in-app (no sito web).
 * Endpoint pubblici (l'utente ha dimenticato la password): throttle stretto.
 */
class SnaPasswordResetController extends Controller
{
    public function __construct(private SnaPasswordResetService $service) {}

    /** Invia un codice all'email indicata, se registrata su SNA (risposta generica). */
    public function request(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        try {
            $this->service->request($data['email']);
        } catch (WordPressUnavailableException $e) {
            // Risposta sempre generica: non riveliamo né errori né esistenza email.
        }

        return ApiResponse::ok([
            'message' => 'Se l\'email è registrata su SNA, riceverai un codice per reimpostare la password.',
        ]);
    }

    /** Verifica il codice e imposta la nuova password sul sito SNA. */
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'code'     => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $ok = $this->service->reset($data['email'], $data['code'], $data['password']);
        } catch (WordPressUnavailableException $e) {
            return ApiResponse::error('Servizio momentaneamente non disponibile. Riprova più tardi.', status: 503);
        }

        if (! $ok) {
            return ApiResponse::error('Codice non valido o scaduto.', status: 422);
        }

        return ApiResponse::ok(['message' => 'Password SNA aggiornata. Ora puoi accedere con la nuova password.']);
    }
}
