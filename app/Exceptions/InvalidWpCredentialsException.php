<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Le credenziali fornite per l'account WordPress non sono valide (401 dal bridge).
 * Distinta da WordPressUnavailableException: qui WP risponde, ma rifiuta le credenziali.
 */
class InvalidWpCredentialsException extends RuntimeException
{
}
