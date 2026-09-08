<?php

use App\Http\Middleware\AuthenticatePublicApi;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;

return [
    'api_path' => 'api',
    'api_domain' => null,
    'export_path' => storage_path('app/openapi.json'),
    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'file',
    ],
    'info' => [
        'version' => env('API_VERSION', '0.1.0'),
        'description' => 'ZAP public HTTP API. Authenticate with a workspace API key (`Authorization: Bearer zap_live_...`). Mutating routes require an Idempotency-Key header.',
    ],
    'ui' => [
        'title' => 'ZAP API',
    ],
    'dev_tools' => [
        'enabled' => false,
    ],
    'renderer' => 'scalar',
    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'laravel',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],
    ],
    'servers' => [
        'Local' => 'api',
    ],
    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => false,
    'flatten_deep_query_parameters' => true,
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],
    'extensions' => [],
    'security_strategy' => [
        MiddlewareAuthSecurityStrategy::class,
        [
            'middleware' => [AuthenticatePublicApi::class],
        ],
    ],
];
