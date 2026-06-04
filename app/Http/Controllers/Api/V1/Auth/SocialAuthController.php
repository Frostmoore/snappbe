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

        try {
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($request->string('token'));
        } catch (\Throwable $e) {
            return ApiResponse::error('Token social non valido.', status: 401);
        }

        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        // Aggancio a un account email esistente (es. utente già registrato via email).
        if (! $user && $socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();
        }

        if ($user) {
            $user->forceFill([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $socialUser->getName() ?: ($socialUser->getNickname() ?: 'Utente SNA'),
                'email' => $socialUser->getEmail() ?: "{$provider}_{$socialUser->getId()}@social.local",
                'role' => UserRole::Member->value,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return ApiResponse::ok([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }
}
