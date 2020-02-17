<?php

$secretPrefix = env('AWS_SECRET_PREFIX', 'dev');

/**
 * Mapper to indicate which config keys use which aws secret names
 */
return [
    'enabled' => env('AWS_SECRET_ENABLED', false),
    'version' => env('AWS_SECRET_VERSION', 'latest'),
    'region' => env('AWS_SECRET_REGION', 'eu-west-1'),

    'collections' => [
        env('AWS_SECRET_DATABASE', $secretPrefix . '/database-credentials') => env(
            'AWS_SECRET_DATABASE_ENABLED', false
        ) ? [
            env('AWS_SECRET_DATABASE_USERNAME', 'username') => 'database.connections.mysql.username',
            env('AWS_SECRET_DATABASE_PASSWORD', 'password') => 'database.connections.mysql.password',
        ] : null,

        env('AWS_SECRET_MAIL', $secretPrefix . '/email-credentials') => env(
            'AWS_SECRET_MAIL_ENABLED', false
        ) ? [
            env('AWS_SECRET_MAIL_USERNAME', 'username') => 'mail.username',
            env('AWS_SECRET_MAIL_PASSWORD', 'password') => 'mail.password',
        ] : null,

        env('AWS_S3_BUCKET', $secretPrefix . '/s3-bucket') => env(
            'AWS_S3_BUCKET_ENABLED', false
        ) ? [
            env('AWS_S3_BUCKET_KEY', 'key') => 'filesystem.disks.s3.key',
            env('AWS_S3_BUCKET_SECRET', 'secret') => 'filesystem.disks.s3.secret',
        ] : null,
    ]
];
