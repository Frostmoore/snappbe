<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Transport push: FcmTransport se le credenziali Firebase ci sono,
        // altrimenti LogPushTransport (così il flusso funziona già in dev).
        $this->app->bind(\App\Notifications\Push\Contracts\PushTransport::class, function ($app) {
            $path = (string) config('snapp.fcm.credentials_path');
            $configured = $path !== '' && (file_exists($path) || file_exists(base_path($path)));

            return $configured
                ? $app->make(\App\Notifications\Push\Transports\FcmTransport::class)
                : $app->make(\App\Notifications\Push\Transports\LogPushTransport::class);
        });

        $this->app->bind(\App\Notifications\Push\Transports\FcmTransport::class, function ($app) {
            return new \App\Notifications\Push\Transports\FcmTransport($app->make('firebase.messaging'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // I JsonResource NON aggiungono il proprio wrapper "data": il wrapping
        // uniforme { "data": ... } lo gestisce ApiResponse, evitando il doppio "data".
        JsonResource::withoutWrapping();

        // L'email di verifica punta alla nostra rotta API firmata; il controller,
        // dopo aver verificato, reindirizza al deep-link dell'app.
        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]);
        });

        // L'email di reset apre direttamente il deep-link dell'app con token+email;
        // l'app poi chiama POST /api/v1/auth/password/reset.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $scheme = config('snapp.deep_link.scheme');
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return "{$scheme}://auth/reset?token={$token}&email={$email}";
        });
    }
}
