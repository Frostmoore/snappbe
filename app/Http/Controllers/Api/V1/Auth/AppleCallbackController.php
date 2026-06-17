<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Callback del flusso WEB di "Sign in with Apple" usato su Android.
 *
 * Apple, finita l'autenticazione, fa un POST (form_post) a questo URL — quello
 * registrato come Return URL nel Services ID. Qui non si autentica nulla: si
 * rimbalzano i parametri (code, id_token, state, user) dentro l'app tramite il
 * deep-link `signinwithapple://callback`, gestito dal plugin sign_in_with_apple.
 * Il backend verificherà poi l'`id_token` quando l'app chiamerà /auth/social/apple.
 */
class AppleCallbackController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        // Apple invia i dati come form-urlencoded (POST) o query (GET).
        $params = $request->all();

        $allowed = ['code', 'id_token', 'state', 'user', 'error'];
        $payload = http_build_query(array_intersect_key($params, array_flip($allowed)));

        // Deep-link gestito dal plugin sul device (scheme registrato nel manifest).
        return redirect()->away('intent://callback?'.$payload.'#Intent;package=com.gsv.snapp;scheme=signinwithapple;end');
    }
}
