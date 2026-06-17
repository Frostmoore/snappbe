<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Transport push (provider attuale): OneSignal se le credenziali ci sono,
        // altrimenti log (il flusso resta esercitabile in dev / senza chiavi).
        $this->app->bind(\App\Notifications\Push\Contracts\AudiencePushTransport::class, function ($app) {
            return $app->make(\App\Services\Push\OneSignalClient::class)->isConfigured()
                ? $app->make(\App\Notifications\Push\Transports\OneSignalTransport::class)
                : $app->make(\App\Notifications\Push\Transports\LogAudiencePushTransport::class);
        });

        // LEGACY: vecchio transport FCM (token-based). Non usato dal flusso attuale,
        // mantenuto per i file storici; risolto solo se richiesto esplicitamente.
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

        // Requisiti password FORTI, validi ovunque si usi Password::defaults()
        // (registrazione, reset, cambio password): min 10, maiuscole+minuscole,
        // almeno un numero e un simbolo. In produzione anche il controllo contro
        // i data breach noti (k-anonymity HaveIBeenPwned); in dev/test lo saltiamo
        // per non dipendere dalla rete ed evitare test flaky.
        Password::defaults(function () {
            $rule = Password::min(10)->mixedCase()->numbers()->symbols();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        // L'email di verifica punta alla nostra rotta API firmata; il controller,
        // dopo aver verificato, mostra una pagina di conferma. L'URL firmato è
        // RELATIVO (validato con `signed:relative`), così possiamo anteporre un
        // host raggiungibile dal client su cui si apre l'email (snapp.web_url),
        // indipendente da APP_URL (che in dev punta a 10.0.2.2 per l'emulatore).
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $path = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ], absolute: false);

            return rtrim((string) config('snapp.web_url'), '/') . $path;
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
