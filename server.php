<?php

/**
 * Router per il PHP built-in server in sviluppo.
 *
 * Avvio (da backend/):
 *   php -S 127.0.0.1:8000 -t public server.php
 *
 * Usiamo questo invece di `php artisan serve` perché su questo ambiente Windows
 * `artisan serve` lancia un processo figlio che NON carica l'estensione `intl`
 * (Filament crasha su Number::format). Vedi utils/codebase_reference.md.
 *
 * Comportamento: serve direttamente i file statici esistenti in public/
 * (css/js/img di Filament, ecc.), e instrada tutto il resto a public/index.php.
 */

$publicPath = __DIR__.'/public';

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false; // lascia che il built-in server serva l'asset statico
}

require_once $publicPath.'/index.php';
