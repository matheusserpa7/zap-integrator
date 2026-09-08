<?php

declare(strict_types=1);
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

return [

    /*
    | First-party Inertia pages use session auth. Public API tokens must not
    | fall back to cookies, so stateful domains stay empty unless overridden.
    */
    'stateful' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', '')),
    ))),

    'guard' => [],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'zap_live_'),

    'routes' => false,

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
