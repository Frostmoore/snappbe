<?php

use Illuminate\Support\Facades\Route;

// Il root non serve una pagina web: rimanda al pannello (Filament reindirizza
// al form di login se non autenticati).
Route::get('/', fn () => redirect('/admin'));
