@echo off
REM ============================================================
REM  Avvio server di sviluppo SNAPP
REM  Si usa `php -S` con il router server.php e NON `php artisan
REM  serve`: su questo ambiente Windows artisan serve avvia un
REM  processo figlio che non parte / non carica intl.
REM  Dettagli: utils/codebase_reference.md
REM ============================================================
php -S 127.0.0.1:8000 -t public server.php
