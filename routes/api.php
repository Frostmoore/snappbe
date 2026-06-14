<?php

use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\PasswordChangeController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AccountLinkController;
use App\Http\Controllers\Api\V1\Webhooks\WordPressWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    | Articoli in proxy live da WordPress (pubblici: app ibrida).
    */
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{id}', [ArticleController::class, 'show'])->whereNumber('id');

    /*
    | Dati globali app (header/logo, social) — pubblici.
    */
    Route::get('app/settings', [\App\Http\Controllers\Api\V1\AppController::class, 'settings']);
    Route::get('app/social-links', [\App\Http\Controllers\Api\V1\AppController::class, 'socialLinks']);
    Route::get('app/sections', [\App\Http\Controllers\Api\V1\AppController::class, 'sections']);

    /*
    | Sezioni app (pubbliche, gestite da pannello BE).
    */
    Route::get('provincial-sections', [\App\Http\Controllers\Api\V1\ProvincialSectionController::class, 'index']);
    Route::get('partners', [\App\Http\Controllers\Api\V1\PartnerController::class, 'index']);
    Route::get('magazine-issues', [\App\Http\Controllers\Api\V1\MagazineIssueController::class, 'index']);
    Route::get('org-chart', [\App\Http\Controllers\Api\V1\OrgChartController::class, 'index']);
    Route::get('events', [\App\Http\Controllers\Api\V1\EventController::class, 'index']);
    Route::get('events/{event}', [\App\Http\Controllers\Api\V1\EventController::class, 'show']);

    // Newsletter = articoli WP categoria "newsletter" (proxy)
    Route::get('newsletters', [ArticleController::class, 'newsletters']);

    // Feed post nativi (filtrati per livello; anonimo = pubblico)
    Route::get('posts', [\App\Http\Controllers\Api\V1\PostController::class, 'index']);
    Route::get('posts/{post}', [\App\Http\Controllers\Api\V1\PostController::class, 'show'])->whereNumber('post');

    /*
    | Registrazione token push del device (anche anonimo).
    */
    Route::post('devices', [\App\Http\Controllers\Api\V1\DeviceController::class, 'store'])->middleware('throttle:30,1');

    /*
    | Webhook da WordPress: solo invalidamento cache (firma HMAC, no auth utente).
    */
    Route::post('webhooks/wordpress', WordPressWebhookController::class)->middleware('wp.signature');

    /*
    | Auth pubblica
    */
    Route::post('auth/register', [RegisterController::class, 'store']);
    Route::post('auth/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
    Route::post('auth/social/{provider}', [SocialAuthController::class, 'store'])->middleware('throttle:10,1');
    // "Accedi con SNA": credenziali snaservice.it → crea utente app + link WP.
    Route::post('auth/sna', [\App\Http\Controllers\Api\V1\Auth\SnaLoginController::class, 'store'])->middleware('throttle:6,1');

    Route::post('auth/password/forgot', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1');
    Route::post('auth/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

    // Verifica email via link firmato (aperto dal browser) → pagina di conferma.
    // Firma RELATIVA: il link usa snapp.web_url come host (vedi AppServiceProvider).
    Route::get('auth/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed:relative', 'throttle:6,1'])
        ->name('verification.verify');

    /*
    | Auth protetta (token Sanctum)
    */
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [SessionController::class, 'me']);
        Route::post('auth/logout', [SessionController::class, 'logout']);
        Route::post('auth/verify/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1');
        Route::post('auth/password/change', [PasswordChangeController::class, 'update']);
        // Conferma password (fallback alla biometria per sbloccare l'area riservata).
        Route::post('auth/password/confirm', [\App\Http\Controllers\Api\V1\Auth\ConfirmPasswordController::class, 'store'])->middleware('throttle:6,1');

        /*
        | Funzioni che richiedono un account ATTIVO (email verificata).
        | Finché l'utente non verifica l'email, qui riceve 403 `email_unverified`.
        */
        Route::middleware('verified')->group(function () {
            // Collegamento account WP↔app (one-to-one) + ereditarietà livello.
            Route::get('account-links', [AccountLinkController::class, 'index']);
            Route::post('account-links', [AccountLinkController::class, 'store']);
            Route::delete('account-links', [AccountLinkController::class, 'destroy']);

            // Area riservata costruita dal pannello (tiles → sezioni → elementi), per ruolo.
            Route::get('reserved/tiles', [\App\Http\Controllers\Api\V1\ReservedController::class, 'tiles']);
            Route::get('reserved/tiles/{tile}', [\App\Http\Controllers\Api\V1\ReservedController::class, 'tile'])->whereNumber('tile');
        });
    });
});
