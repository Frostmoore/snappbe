<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmailVerificationController extends Controller
{
    /**
     * Verifica email via link firmato (cliccato dal browser dell'email client).
     * Al termine reindirizza al deep-link dell'app.
     */
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw new AccessDeniedHttpException('Link di verifica non valido.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $scheme = config('snapp.deep_link.scheme');

        return redirect()->away("{$scheme}://auth/verify?status=success");
    }

    /**
     * Reinvio dell'email di verifica all'utente autenticato.
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::ok(['message' => 'Email già verificata.']);
        }

        $user->sendEmailVerificationNotification();

        return ApiResponse::ok(['message' => 'Email di verifica inviata.']);
    }
}
