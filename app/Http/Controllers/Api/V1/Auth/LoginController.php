<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Login email/password → rilascia un token Sanctum.
     * Messaggio generico in caso di credenziali errate (no user enumeration).
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! $user->password || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return ApiResponse::ok([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }
}
