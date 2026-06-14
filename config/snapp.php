<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integrazione WordPress (proxy live)
    |--------------------------------------------------------------------------
    | base_url     URL del sito WP che espone il plugin snapp-connector.
    | hmac_secret  Segreto condiviso per firmare/verificare i webhook e il
    |              bridge di verifica account. Deve coincidere con quello
    |              impostato nelle settings del plugin WordPress.
    */
    'wordpress' => [
        'base_url'    => env('SNAPP_WP_BASE_URL', ''),
        'hmac_secret' => env('SNAPP_WP_HMAC_SECRET', ''),
        // Chiave PRIVATA Ed25519 (base64) per firmare le richieste verso WP.
        // Se valorizzata, sostituisce l'HMAC sul canale Laravel→WP (WP tiene solo
        // la chiave pubblica). Generala con `php artisan snapp:gen-signing-key`.
        'signing_secret_key' => env('SNAPP_SIGNING_SECRET_KEY', ''),
        'rest_path'   => '/wp-json/snapp/v1',
        'timeout'     => 8,
        // Slug della pagina WP "il mio account" mostrata nativamente nell'area riservata.
        'account_page_slug' => env('SNAPP_WP_ACCOUNT_PAGE', 'mio-account'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social OAuth (login app)
    |--------------------------------------------------------------------------
    */
    'oauth' => [
        'google' => [
            'client_id'     => env('GOOGLE_CLIENT_ID', ''),
            'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        ],
        'apple' => [
            'client_id'     => env('APPLE_CLIENT_ID', ''),
            'client_secret' => env('APPLE_CLIENT_SECRET', ''),
        ],
        // SOLO DEV: consente il login social mock (senza credenziali reali) per
        // sviluppare/dimostrare il flusso. Ignorato in produzione (vedi controller).
        'mock' => env('SNAPP_OAUTH_MOCK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (push)
    |--------------------------------------------------------------------------
    */
    'fcm' => [
        'project_id'       => env('FCM_PROJECT_ID', ''),
        'credentials_path' => env('FCM_CREDENTIALS_PATH', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deep-link app (vedi plan §Appendice A)
    |--------------------------------------------------------------------------
    | scheme  Schema custom dell'app (es. snapp://...). Usato negli URL delle
    |         email di verifica/reset per riportare l'utente nell'app.
    */
    'deep_link' => [
        'scheme' => env('SNAPP_DEEP_LINK_SCHEME', 'snapp'),
    ],

    /*
    |--------------------------------------------------------------------------
    | URL web pubblico del backend
    |--------------------------------------------------------------------------
    | Base usata nel link di verifica email: deve essere raggiungibile dal
    | client su cui si apre l'email. In dev APP_URL punta a 10.0.2.2 (per le
    | immagini dell'emulatore), ma quell'host non esiste fuori dall'emulatore;
    | l'email si apre dal PC, quindi qui serve un host raggiungibile (127.0.0.1).
    | In produzione: il dominio reale (uguale ad APP_URL).
    */
    'web_url' => env('SNAPP_WEB_URL', env('APP_URL')),

];
