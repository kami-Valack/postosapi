<?php

return [
    /*
    | URL pública da API (HTTPS em produção). Usada por Swagger, Redoc e route().
    | Ex.: https://postos.pinpointech.com
    */
    'public_url' => env('APP_PUBLIC_URL', env('APP_URL', 'https://postos.pinpointech.com')),

    /*
    | Forçar https:// em todos os links gerados. null = auto (se public_url começa com https)
    */
    'force_https' => env('FORCE_HTTPS'),

    /*
    | Caminhos da documentação (relativos = mesmo protocolo da página, evita mixed-content)
    */
    'swagger_spec_path' => env('POSTOS_SWAGGER_SPEC_PATH', '/docs?api-docs.json'),
    'openapi_json_path' => env('POSTOS_OPENAPI_JSON_PATH', '/api/docs/openapi.json'),

    'search' => [
        'min_query_length' => 2,
        'default_limit' => 20,
        'max_limit' => 50,
        'cache_ttl_seconds' => 60,
    ],
];
