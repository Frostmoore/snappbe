<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SocialAuthController extends Controller
{
    /**
     * Provider supportati. NB: 'apple' richiede il pacchetto socialiteproviders/apple
     * (da aggiungere quando configureremo Apple); 'google' è coperto da Socialite core.
     */
    private const SUPPORTED = ['google', 'apple'];

    /**
     * Login social stateless: l'app ottiene l'id_token/access_token dal provider
     * nativamente e lo invia qui; verifichiamo, troviamo/creiamo l'utente e rilasciamo
     * un token Sanctum.
     */
    public function store(SocialLoginRequest $request, string $provider): JsonResponse
    {
        if (! in_array($provider, self::SUPPORTED, true)) {
            throw new NotFoundHttpException('Provider non supportato.');
        }

        // Identità normalizzata {id, email, name}: da Socialite (reale) o mock (dev).
        $identity = $this->resolveIdentity($request, $provider);
        if ($identity === null) {
            return ApiResponse::error('Token social non valido.', status: 401);
        }
        if ($identity === false) {
            return ApiResponse::error('Login social non configurato.', status: 501);
        }

        $user = User::where('provider', $provider)
            ->where('provider_id', $identity['id'])
            ->first();

        // Aggancio a un account email esistente (es. utente già registrato via email).
        if (! $user && $identity['email']) {
            $user = User::where('email', $identity['email'])->first();
        }

        if ($user) {
            $user->forceFill([
                'provider' => $provider,
                'provider_id' => $identity['id'],
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $identity['name'] ?: 'Utente SNA',
                'email' => $identity['email'] ?: "{$provider}_{$identity['id']}@social.local",
                'role' => UserRole::Member->value,
                'provider' => $provider,
                'provider_id' => $identity['id'],
            ]);
            // Gli IdP social restituiscono email già verificate → account attivo.
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return ApiResponse::ok([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Risolve l'identità social.
     *
     * @return array{id:string,email:?string,name:?string}|null|false
     *   array = identità valida; null = token non valido; false = non configurato.
     */
    private function resolveIdentity(SocialLoginRequest $request, string $provider): array|null|false
    {
        // 1) Reale: solo se il provider ha le credenziali configurate.
        if (config("snapp.oauth.{$provider}.client_id")) {
            try {
                $u = Socialite::driver($provider)->stateless()->userFromToken($request->string('token'));
            } catch (\Throwable $e) {
                return null;
            }

            return [
                'id'    => (string) $u->getId(),
                'email' => $u->getEmail(),
                'name'  => $u->getName() ?: $u->getNickname(),
            ];
        }

        // 2) Mock: SOLO fuori produzione e con flag esplicito. In prod è irraggiungibile.
        if (! app()->isProduction() && (bool) config('snapp.oauth.mock')) {
            return [
                'id'    => 'mock-'.$provider,
                'email' => (string) ($request->input('email') ?: "{$provider}.mock@sna.it"),
                'name'  => (string) ($request->input('name') ?: 'Utente '.ucfirst($provider).' (mock)'),
            ];
        }

        // 3) Né reale né mock → non configurato.
        return false;
    }
}
