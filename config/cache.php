<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    | Supported: "apc", "array", "database", "file", "memcached", "redis"
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT  => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'client' => 'predis',
            'cluster' => env('REDIS_CLUSTER', false),

            // Note! for single redis nodes, the default is defined here.
            // keeping it here for clusters will actually prevent the cluster config
            // from being used, it'll assume single node only.
            //'default' => [
            //    ...
            //],

            // #pro-tip, you can use the Cluster config even for single instances!
            'clusters' => [
                'default' => [
                    [
                        'scheme'   => env('REDIS_SCHEME', 'tcp'),
                        'host'     => env('REDIS_HOST', 'localhost'),
                        'password' => env('REDIS_PASSWORD', null),
                        'port'     => env('REDIS_PORT', 6379),
                        'database' => env('REDIS_DATABASE', 0),
                    ],
                ],
                'options' => [ // Clustering specific options
                    'cluster' => 'redis', // This tells Redis Client lib to follow redirects (from cluster)
                ]
            ],
            'options' => [
                'parameters' => [ // Parameters provide defaults for the Connection Factory
                    'password' => env('REDIS_PASSWORD', null), // Redirects need PW for the other nodes
                    'scheme'   => env('REDIS_SCHEME', 'tcp'),  // Redirects also must match scheme
                ],
                'ssl'    => ['verify_peer' => false], // Since we dont have TLS cert to verify
            ]
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a RAM based store such as APC or Memcached, there might
    | be other applications utilizing the same cache. So, we'll specify a
    | value to get prefixed to all our keys so we can avoid collisions.
    |
    */

    'prefix' => env(
        'CACHE_PREFIX',
        str_slug(env('APP_NAME', 'laravel'), '_').'_cache'
    ),

];
