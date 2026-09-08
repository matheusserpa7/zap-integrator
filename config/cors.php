<?php

declare(strict_types=1);

return [

    /*
    | The public API is server-to-server. Matching no paths means browsers
    | do not receive Access-Control-Allow-Origin headers.
    */
    'paths' => [],

    'allowed_methods' => [],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
