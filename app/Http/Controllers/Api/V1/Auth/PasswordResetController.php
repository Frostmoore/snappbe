<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Invia il link di reset (deep-link). Risposta sempre positiva per non rivelare
     * se l'email esiste (anti user-enumeration).
     */
    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return ApiResponse::ok([
            'message' => 'Se l\'email è registrata, riceverai le istruzioni per il reset.',
        ]);
    }

    /**
     * Esegue il reset con token+email+nuova password.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return ApiResponse::ok(['message' => 'Password reimpostata con successo.']);
    }
}
