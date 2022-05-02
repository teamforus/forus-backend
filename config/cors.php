<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    */

    /*
     * You can enable CORS for 1 or multiple paths.
     * Example: ['api/*']
     */
    'paths' => [
        'api/*'
    ],

    /*
    * Matches the request method. `[*]` allows all methods.
    */
    'allowed_methods' => [
        '*'
    ],

    /*
     * Matches the request origin. `[*]` allows all origins.
     */
    'allowed_origins' => [
        '*'
    ],

    /*
     * Matches the request origin with, similar to `Request::is()`
     */
    'allowed_origins_patterns' => [],

    /*
     * Sets the Access-Control-Allow-Headers response header. `[*]` allows all headers.
     */
    'allowed_headers' => [
        'Content-Type', 'Access-Control-Allow-Headers', 'Authorization',
        'X-Requested-With', 'Locale', 'Client-Key', 'Client-Type', 'Client-Version', 'Accept',
        'Access-Token', 'Accept-Language'
    ],

    /*
     * Sets the Access-Control-Expose-Headers response header.
     */
    'exposed_headers' => [
        'Error-Code'
    ],

    /*
     * Sets the Access-Control-Max-Age response header.
     */
    'max_age' => 0,

    /*
     * Sets the Access-Control-Allow-Credentials header.
     */
    'supports_credentials' => false,
];
