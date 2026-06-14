<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmailVerificationController extends Controller
{
    /**
     * Verifica email via link firmato (cliccato dal browser dell'email client).
     * Mostra una pagina di conferma: l'utente torna nell'app, che rileva lo stato
     * verificato al successivo /me. (Niente redirect a deep-link: lo schema custom
     * non è ancora registrato sul device.)
     */
    public function verify(Request $request, int $id, string $hash): Response
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw new AccessDeniedHttpException('Link di verifica non valido.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response($this->successPage(), 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /** Pagina HTML minimale mostrata dopo la verifica. */
    private function successPage(): string
    {
        $app = e(config('app.name', 'SNAPP'));

        return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email verificata · {$app}</title>
<style>
  :root { color-scheme: light dark; }
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
         font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#f4f5f7; }
  .card { background:#fff; max-width:380px; width:90%; padding:40px 28px; border-radius:16px;
          box-shadow:0 8px 30px rgba(0,0,0,.08); text-align:center; }
  .check { width:72px; height:72px; border-radius:50%; background:#e6f7ec; color:#1aaf54;
           display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:38px; }
  h1 { font-size:20px; margin:0 0 10px; color:#1f2430; }
  p { color:#5a6172; line-height:1.5; margin:0; }
</style>
</head>
<body>
  <div class="card">
    <div class="check">&#10003;</div>
    <h1>Email verificata!</h1>
    <p>Il tuo account {$app} è ora attivo. Torna nell'app per continuare.</p>
  </div>
</body>
</html>
HTML;
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
