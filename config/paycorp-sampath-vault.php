<?php

/*
|--------------------------------------------------------------------------
| Sampath Bank (Paycorp) Internet Payment Gateway
|--------------------------------------------------------------------------
|
| Every secret is read from the environment. Nothing in this file may hold a
| real credential: it is committed to version control and published into
| applications, and the previous version of this package shipped live
| production credentials here, which is why they must now be treated as
| compromised and rotated.
|
| env() is called ONLY in this file. Calling it from application code breaks
| under `php artisan config:cache`, where env() returns null -- the old
| classes read env() in their constructors, so a cached-config deployment
| signed every request with an empty HMAC secret and an empty endpoint.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Service endpoint
    |--------------------------------------------------------------------------
    |
    | The gateway REST proxy URL. Must be https:// -- cardholder data is not
    | sent over plain HTTP unless allow_insecure_endpoint is explicitly set.
    |
    */

    'service_endpoint' => env('SAMPATH_SERVICE_ENDPOINT', ''),

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    */

    'authtoken' => env('SAMPATH_AUTHTOKEN', ''),

    'hmac_secret' => env('SAMPATH_HMAC', ''),

    /*
    |--------------------------------------------------------------------------
    | Merchant client identifiers
    |--------------------------------------------------------------------------
    |
    | Paycorp issues separate client ids for tokenising (hosted page) and for
    | purchasing (real-time). Using the wrong one produces a rejected request.
    |
    */

    'tokenize_client_id' => env('SAMPATH_TOKENIZE_CLIENT_ID', ''),

    'purchase_client_id' => env('SAMPATH_PURCHASE_CLIENT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Transaction defaults
    |--------------------------------------------------------------------------
    */

    'currency' => env('SAMPATH_CURRENCY', ''),

    'return_url' => env('SAMPATH_RETURN_URL', ''),

    'cancel_url' => env('SAMPATH_CANCEL_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Request timezone
    |--------------------------------------------------------------------------
    |
    | Timezone used to stamp requestDate. Leave null to follow the application
    | timezone, which is what the legacy client did. Pinning it (for example
    | to Asia/Colombo) keeps settlement windows and duplicate detection
    | consistent across deployments in different regions.
    |
    */

    'timezone' => env('SAMPATH_TIMEZONE'),

    /*
    |--------------------------------------------------------------------------
    | Validate only
    |--------------------------------------------------------------------------
    |
    | When true the gateway validates the request without moving money.
    |
    */

    'validate_only' => env('SAMPATH_VALIDATE_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Error handling
    |--------------------------------------------------------------------------
    |
    | false (default) keeps the historical contract: every method returns an
    | array and signals problems with 'status' => false plus a 'msg'.
    |
    | true makes the methods rethrow instead, so a transport failure cannot be
    | mistaken for a decline. Recommended for new code -- see the exception
    | classes under src/Exceptions for what can be caught.
    |
    */

    'throw_on_error' => env('SAMPATH_THROW_ON_ERROR', false),

    /*
    |--------------------------------------------------------------------------
    | Insecure endpoint escape hatch
    |--------------------------------------------------------------------------
    |
    | Permits a non-HTTPS service endpoint. Intended only for a local sandbox.
    | Never enable this in production: it puts PANs and CVVs on the wire in
    | the clear.
    |
    */

    'allow_insecure_endpoint' => env('SAMPATH_ALLOW_INSECURE_ENDPOINT', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP transport
    |--------------------------------------------------------------------------
    */

    'transport' => [

        /*
         | Seconds to wait for the TCP/TLS connection. The legacy default of 60
         | is kept so no merchant on a slow link starts failing.
         */
        'connect_timeout' => env('SAMPATH_CONNECT_TIMEOUT', 60),

        /*
         | Ceiling on the whole request. The legacy client had none, so a
         | stalled gateway pinned a PHP worker until the process was killed.
         */
        'timeout' => env('SAMPATH_TIMEOUT', 120),

        /*
         | TLS certificate verification.
         |
         | SECURITY: the legacy client hard-coded this off, so every PAN and
         | CVV it sent travelled over a connection that accepted any
         | certificate and was readable by an active man-in-the-middle. Leave
         | this true. If verification fails, fix the CA bundle below -- do not
         | disable the check.
         */
        'verify_peer' => env('SAMPATH_VERIFY_SSL', true),

        /*
         | Absolute path to a CA bundle, for hosts whose system store is
         | incomplete. Null uses the system default.
         */
        'ca_bundle' => env('SAMPATH_CA_BUNDLE'),

        'proxy_host' => env('SAMPATH_PROXY_HOST'),

        'proxy_port' => env('SAMPATH_PROXY_PORT'),

    ],

];
