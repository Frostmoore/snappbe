<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Riallinea giornalmente i livelli di iscrizione dagli account WP collegati.
Schedule::command('snapp:sync-levels')->daily();

// Tiene caldi WP + cache articoli/newsletter: il primo accesso non trova mai WP freddo.
Schedule::command('snapp:warm-articles')->everyMinute()->withoutOverlapping();
