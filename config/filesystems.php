<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => true,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            // 'visibility' => 'public',
            'throw' => true,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'throw' => true,
        ],

        's3_media' => [
            'driver' => 's3',
            'key' => env('AWS_MEDIA_ACCESS_KEY_ID'),
            'secret' => env('AWS_MEDIA_SECRET_ACCESS_KEY'),
            'region' => env('AWS_MEDIA_DEFAULT_REGION'),
            'bucket' => env('AWS_MEDIA_BUCKET'),
            'url' => env('AWS_MEDIA_URL'),
            'throw' => true,
        ],

        's3_files' => [
            'driver' => 's3',
            'key' => env('AWS_FILES_ACCESS_KEY_ID'),
            'secret' => env('AWS_FILES_SECRET_ACCESS_KEY'),
            'region' => env('AWS_FILES_DEFAULT_REGION'),
            'bucket' => env('AWS_FILES_BUCKET'),
            'url' => env('AWS_FILES_URL'),
            'throw' => true,
        ],

        'ftp_physical_cards' => [
            'driver' => 'ftp',
            'host' => env('PHYSICAL_CARDS_FTP_HOST'),
            'username' => env('PHYSICAL_CARDS_FTP_USER'),
            'password' => env('PHYSICAL_CARDS_FTP_PASS'),
            'throw' => true,
        ],
    ],

];
