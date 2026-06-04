<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /** Profilo dell'utente autenticato. */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::ok(new UserResource($request->user()));
    }

    /** Logout: revoca solo il token corrente. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::ok(['message' => 'Logout effettuato.']);
    }
}
