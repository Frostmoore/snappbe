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

    Route::post('auth/password/forgot', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1');
    Route::post('auth/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

    // Verifica email via link firmato (aperto dal browser) → redirect deep-link app.
    Route::get('auth/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    /*
    | Auth protetta (token Sanctum)
    */
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [SessionController::class, 'me']);
        Route::post('auth/logout', [SessionController::class, 'logout']);
        Route::post('auth/verify/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1');
        Route::post('auth/password/change', [PasswordChangeController::class, 'update']);

        // Collegamento account WP↔app (one-to-one) + ereditarietà livello.
        Route::get('account-links', [AccountLinkController::class, 'index']);
        Route::post('account-links', [AccountLinkController::class, 'store']);
        Route::delete('account-links', [AccountLinkController::class, 'destroy']);
    });
});
