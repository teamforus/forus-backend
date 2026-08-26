<?php

use Illuminate\Support\Env;

return [
    /*
    |--------------------------------------------------------------------------
    | PDF To Image
    |--------------------------------------------------------------------------
    |
    | Supported connections: "aws", "local".
    |
    */

    'enabled' => env('PDF_TO_IMG_ENABLED', false),
    'default' => env('PDF_TO_IMG_CONNECTION', 'aws'),
    'log_channel' => env('PDF_TO_IMG_LOG_CHANNEL', 'pdf_to_img'),

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | aws: invokes Lambda and exchanges files through S3.
    | local: invokes host Poppler binaries.
    |
    */

    'connections' => [
        'aws' => [
            'driver' => 'aws',

            'credentials' => [
                'key' => env('PDF_TO_IMG_AWS_ACCESS_KEY_ID'),
                'secret' => env('PDF_TO_IMG_AWS_SECRET_ACCESS_KEY'),
                'token' => env('PDF_TO_IMG_AWS_SESSION_TOKEN'),
            ],

            'lambda' => [
                'region' => env('PDF_TO_IMG_AWS_REGION', env('AWS_DEFAULT_REGION', 'eu-west-1')),
                'function_name' => env('PDF_TO_IMG_AWS_FUNCTION_NAME', 'pdf-to-img-lambda-service'),
                'qualifier' => env('PDF_TO_IMG_AWS_QUALIFIER'),
                'timeout' => intval(env('PDF_TO_IMG_AWS_TIMEOUT', 30)),
            ],

            'storage' => [
                'disk' => env('PDF_TO_IMG_AWS_DISK', 's3_pdf_to_img'),
                'input_prefix' => env('PDF_TO_IMG_AWS_INPUT_PREFIX', 'pdf-to-img/' . env('APP_ENV', 'local') . '/input'),
                'output_prefix' => env('PDF_TO_IMG_AWS_OUTPUT_PREFIX', 'pdf-to-img/' . env('APP_ENV', 'local') . '/output'),
            ],

            'cleanup' => env('PDF_TO_IMG_AWS_CLEANUP', true),
        ],

        'local' => [
            'driver' => 'local',

            'binaries' => [
                'pdfinfo' => env('PDF_TO_IMG_LOCAL_PDFINFO_BINARY', 'pdfinfo'),
                'pdftoppm' => env('PDF_TO_IMG_LOCAL_PDFTOPPM_BINARY', 'pdftoppm'),
            ],

            'storage' => [
                'disk' => env('PDF_TO_IMG_LOCAL_DISK', 'local'),
                'path' => env('PDF_TO_IMG_LOCAL_PATH', 'pdf-to-img/tmp'),
            ],

            'timeout' => intval(env('PDF_TO_IMG_LOCAL_TIMEOUT', 30)),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Render Defaults
    |--------------------------------------------------------------------------
    |
    | quality: 1-100. oversize: "scale", "error".
    |
    */

    'defaults' => [
        'dpi' => 300,
        'quality' => 75,
        'max_pages' => 15,
        'max_width' => 2000,
        'max_height' => 2000,
        'oversize' => 'scale',
        'strict_page_validation' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | Test-only controls for local Poppler dependent tests.
    |
    */

    'testing' => [
        'skip_local_poppler_tests' => filter_var(
            Env::get('PDF_TO_IMG_SKIP_LOCAL_POPPLER_TESTS', false),
            FILTER_VALIDATE_BOOLEAN,
        ),
    ],
];
