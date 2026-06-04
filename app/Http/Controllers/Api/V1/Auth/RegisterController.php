<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * Registrazione con email/password. Crea l'utente come `member` non verificato
     * e invia l'email di verifica (deep-link). Rilascia subito un token così l'app
     * può proseguire; le aree che lo richiedono useranno il middleware `verified`.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'role' => UserRole::Member->value,
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return ApiResponse::created([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }
}
