<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Punto unico di formattazione delle risposte API.
 *
 * Contratto (vedi plan §Principi):
 *   - successo  → { "data": ..., "meta": {...}? }
 *   - errore    → { "message": "...", "errors": {...}? }
 *
 * `meta`/`errors` sono inclusi solo se valorizzati, per non sporcare il payload.
 * Tutti i controller API e l'exception handler passano da qui: così il client
 * Flutter ha una forma stabile e un solo posto da cambiare se il contratto evolve.
 */
class ApiResponse
{
    public static function ok(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function created(mixed $data = null, array $meta = []): JsonResponse
    {
        return static::ok($data, $meta, 201);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Risposta di errore uniforme.
     *
     * @param  array<string, array<int, string>>  $errors  Mappa campo → messaggi (stile validazione Laravel).
     */
    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        $payload = ['message' => $message];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
