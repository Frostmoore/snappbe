<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifica il contratto di errore uniforme delle API (Fase 1.2).
 * Non tocca il DB: esercita solo l'exception handler.
 */
class ApiErrorFormatTest extends TestCase
{
    public function test_unauthenticated_request_returns_401_json(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Non autenticato.']);
    }

    public function test_unknown_api_route_returns_404_json(): void
    {
        $this->getJson('/api/rotta-inesistente')
            ->assertStatus(404)
            ->assertExactJson(['message' => 'Risorsa non trovata.']);
    }
}
