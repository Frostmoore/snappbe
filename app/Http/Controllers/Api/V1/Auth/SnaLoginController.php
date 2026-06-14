<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\WordPress\AccountLinker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * "Accedi con SNA": l'utente fornisce le credenziali del sito snaservice.it.
 * Le verifichiamo contro WordPress (bridge HMAC), creiamo/troviamo l'utente app
 * e lo colleghiamo direttamente all'account WP (ereditando il livello), poi
 * rilasciamo un token Sanctum.
 *
 * Rotta pubblica → throttle stretto: questo endpoint può essere usato per
 * provare credenziali WP, quindi va limitato come il login.
 */
class SnaLoginController extends Controller
{
    public function __construct(private AccountLinker $linker) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'identifier'  => ['required', 'string', 'max:255'],
            'password'    => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        // 1) Verifica le credenziali contro WP (422 coerente se errate/inesistenti).
        $data = $this->linker->verifyOrFail(
            (string) $request->string('identifier'),
            (string) $request->string('password'),
        );

        $email = $data['email'] ?? null;
        if (! $email) {
            throw ValidationException::withMessages([
                'identifier' => ['Il tuo account SNA non ha un indirizzo email associato. Contatta l\'assistenza.'],
            ]);
        }

        // 2) Trova o crea l'utente app a partire dall'email dell'account WP.
        $user = User::where('email', $email)->first();
        if (! $user) {
            $user = User::create([
                'name'     => $data['username'] ?? 'Iscritto SNA',
                'email'    => $email,
                // Stessa password del sito SNA: così l'utente potrà accedere anche
                // con email+password, non solo con "Accedi con SNA".
                'password' => (string) $request->string('password'),
                'role'     => UserRole::Member->value,
            ]);
        }
        // L'account WP ha email verificata → account app attivo.
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        // 3) Collega all'account WP (riusa i dati già verificati, niente seconda chiamata).
        $this->linker->linkWithData($user, $data);

        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return ApiResponse::ok([
            'token' => $token,
            'user'  => new UserResource($user->fresh()),
        ]);
    }
}
