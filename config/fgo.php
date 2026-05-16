<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Credential Resolver
    |--------------------------------------------------------------------------
    |
    | Optional. Point this at a class that implements
    | FgoApi\Laravel\Contracts\CredentialsResolver, or a callable
    | ([Class::class, 'method'] or a Closure registered in a service provider),
    | and the bundled FgoManager will use it to look up credentials at runtime
    | instead of reading the values below.
    |
    | Typical use cases:
    |   - per-tenant credentials stored in a database
    |   - per-user credentials in a multi-merchant SaaS
    |   - credentials from a secrets manager
    |
    | Examples:
    |   'resolver' => \App\Fgo\TenantCredentialsResolver::class,
    |   'resolver' => [\App\Fgo\Repository::class, 'currentCredentials'],
    |
    | When null, the static values below are used for the "default" client.
    |
    */

    'resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Static FGO API Credentials
    |--------------------------------------------------------------------------
    |
    | Used when no resolver is configured. Pulls from the environment so you
    | never commit secrets. Ignored entirely when a resolver is set.
    |
    */

    'cod_unic'    => env('FGO_COD_UNIC', ''),
    'private_key' => env('FGO_PRIVATE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Platform URL
    |--------------------------------------------------------------------------
    */

    'platform_url' => env('FGO_PLATFORM_URL', env('APP_URL', '')),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | "test" hits the UAT sandbox, "production" hits live. You can also pass a
    | full custom URL string.
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
