<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'wp.signature' => \App\Http\Middleware\VerifyWordPressSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tutte le richieste API rispondono sempre in JSON.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Resa uniforme degli errori sotto /api/* tramite ApiResponse
        // (contratto { message, errors? }). Le richieste web restano al default.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => ApiResponse::error(
                    $e->getMessage(),
                    $e->errors(),
                    $e->status,
                ),
                $e instanceof AuthenticationException => ApiResponse::error(
                    'Non autenticato.',
                    status: 401,
                ),
                $e instanceof AuthorizationException => ApiResponse::error(
                    $e->getMessage() ?: 'Azione non consentita.',
                    status: 403,
                ),
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => ApiResponse::error(
                    'Risorsa non trovata.',
                    status: 404,
                ),
                // Altri HttpException (405, 429, ...) mantengono il loro status.
                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    $e->getMessage() ?: 'Errore nella richiesta.',
                    status: $e->getStatusCode(),
                ),
                // Errore non gestito: dettaglio solo in debug, altrimenti messaggio generico.
                default => ApiResponse::error(
                    config('app.debug') ? $e->getMessage() : 'Errore interno del server.',
                    status: 500,
                ),
            };
        });
    })->create();
