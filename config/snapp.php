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
        'rest_path'   => '/wp-json/snapp/v1',
        'timeout'     => 8,
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

];
