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
        // Timeout verso WP: a "freddo" il proxy WP può metterci diversi secondi
        // (PHP-FPM/opcache + WP non in cache). Margine ampio per non dare 503 al
        // primo accesso; le richieste successive sono in cache (ArticleCache).
        'timeout'     => (int) env('SNAPP_WP_TIMEOUT', 20),
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
            // Bundle ID dell'app iOS = audience del token nel login NATIVO iOS.
            // Funge anche da "interruttore configurato" del provider apple.
            'client_id'     => env('APPLE_CLIENT_ID', ''),
            // Services ID = audience del token nel flusso WEB (Android).
            'services_id'   => env('APPLE_SERVICES_ID', ''),
            'team_id'       => env('APPLE_TEAM_ID', ''),
            'key_id'        => env('APPLE_KEY_ID', ''),
            'client_secret' => env('APPLE_CLIENT_SECRET', ''),
        ],
        // SOLO DEV: consente il login social mock (senza credenziali reali) per
        // sviluppare/dimostrare il flusso. Ignorato in produzione (vedi controller).
        'mock' => env('SNAPP_OAUTH_MOCK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifiche push — OneSignal (provider attuale)
    |--------------------------------------------------------------------------
    | Targeting per external user id (= id utente app) e per segmento. Le
    | credenziali si compilano nel .env quando disponibili; se mancano, il
    | transport ricade su log (nessun invio, nessun crash).
    */
    'onesignal' => [
        'app_id'       => env('ONESIGNAL_APP_ID', ''),
        'rest_api_key' => env('ONESIGNAL_REST_API_KEY', ''),
        'api_url'      => env('ONESIGNAL_API_URL', 'https://onesignal.com/api/v1/notifications'),
        // Segmento OneSignal usato per il target "tutti". Nel modello nuovo di
        // OneSignal il segmento di default che include tutte le iscrizioni è
        // "Total Subscriptions" ("Subscribed Users" risulta vuoto).
        'all_segment'  => env('ONESIGNAL_ALL_SEGMENT', 'Total Subscriptions'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (push) — LEGACY (sostituito da OneSignal)
    |--------------------------------------------------------------------------
    | Config storica del vecchio transport FCM, mantenuta per i file legacy
    | (FcmTransport/Device). Non usata dal flusso push attuale.
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
