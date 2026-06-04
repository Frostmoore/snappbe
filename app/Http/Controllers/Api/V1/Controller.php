<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Base controller dell'area API v1.
 *
 * Aggiunge i trait che il base controller minimale di Laravel 12 NON include:
 *  - AuthorizesRequests → abilita authorize()/authorizeResource() per le Policy (accesso clienti per livello);
 *  - ValidatesRequests  → abilita validate() per la validazione inline quando non si usa una Form Request.
 *
 * Tutti i controller sotto App\Http\Controllers\Api\V1 estendono questo.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
