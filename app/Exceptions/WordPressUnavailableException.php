<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Sollevata quando il sito WordPress (proxy live) non è raggiungibile o risponde
 * con errore. I controller la traducono in una risposta 503 pulita.
 */
class WordPressUnavailableException extends RuntimeException
{
}
