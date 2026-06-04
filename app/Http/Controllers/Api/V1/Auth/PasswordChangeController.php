<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordChangeController extends Controller
{
    /**
     * Cambio password dell'utente autenticato (richiede la password attuale).
     * Revoca gli altri token per forzare il re-login degli altri device.
     */
    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => $request->string('password'),
        ])->save();

        // Mantiene valido solo il token corrente.
        $currentTokenId = $user->currentAccessToken()->id;
        $user->tokens()->whereKeyNot($currentTokenId)->delete();

        return ApiResponse::ok(['message' => 'Password aggiornata con successo.']);
    }
}
