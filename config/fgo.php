<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | FGO API Credentials
    |--------------------------------------------------------------------------
    |
    | Your unique FGO account code (CUI) and the private API key generated in
    | the FGO Settings panel. Keep these out of version control.
    |
    */

    'cod_unic'    => env('FGO_COD_UNIC', ''),
    'private_key' => env('FGO_PRIVATE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Platform URL
    |--------------------------------------------------------------------------
    |
    | Identifies your application to FGO (sent as PlatformaUrl). Typically the
    | base URL of the app integrating with the API.
    |
    */

    'platform_url' => env('FGO_PLATFORM_URL', env('APP_URL', '')),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | "test" hits the UAT sandbox, "production" hits live. You can also pass a
    | full custom URL if FGO ever exposes a self-hosted endpoint for you.
    |
    */

    'environment' => env('FGO_ENVIRONMENT', 'test'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('FGO_TIMEOUT', 20),
];
