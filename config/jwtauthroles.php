<?php

return [
    'userModel' => env('FA_USER_MODEL', 'App\\Models\\User'),

    // If enabled, stores every user in the database
    'useDB' => env('FA_USE_DB', false),

    // Only if useDB = true
    // Column name in the users table where uuid should be stored.'
    'userId' => env('FA_USR_ID', 'uuid'),
    // Only if useDB = true
    'autoCreateUser' => env('FA_CREATE_USR', false),

    'alg' => env('FA_ALG', 'RS256'),

    // Allows you to skip validation, this is potentially dangerous,
    // only use for testing or if the jwt has been validated by something like an api gateway
    'validateJwt' => env('FA_VALIDATE', true),

    // Only if validateJwt = true
    'cache' => [
        'enabled' => env('FA_CACHE_ENABLED', false),
        'type' => env('FA_CACHE_TYPE', 'database'),
    ],

    // Only if validateJwt = true
    'jwkUri' => env('JWKS_URL', 'http://localhost:9011/.well-known/jwks.json'),
    // Only if validateJwt = true
    'pemUri' => env('PEM_URL', 'http://localhost:9011/api/jwt/public-key'),

    // Only if validateJwt = true
    // Configure to use PEM endpoint (default) or JWK
    'useJwk' => env('USE_JWK', false),

];
