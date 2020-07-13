<?php

return [
    'jwkUri' => env('JWKS_URL', 'http://localhost:9011/.well-known/jwks.json'),
    'pemUri' => env('PEM_URL', 'http://localhost:9011/api/jwt/public-key'),

    // Configure to use PEM endpoint (default) or JWK
    'useJwk' => env('USE_JWK', true),

    // Column name in the users table where uuid should be stored. Defaults to id but can be another column like 'uuid'
    'userId' => env('FA_USR_ID', 'id'),

    'autoCreateUser' => env('FA_CREATE_USR', true),

    // This uses spatie's permissions package to give users the fusionauth roles.
    // To use this, installing the permissions package is required
    'usePermissions' => env('FA_USE_PERM', true),
    // Creates a role if not found in database
    'autoCreateRoles' => env('FA_CREATE_ROLES', true),

    'alg' => env('FA_ALG', 'RS256'),

    'cache' => [
        'enabled' => env('FA_CACHE_ENABLED', false),
        'type' => env('FA_CACHE_TYPE', 'database'),
    ],
];
