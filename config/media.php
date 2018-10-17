<?php

return [
    'filesystem_driver'     => env('IMAGES_STORAGE_DRIVER', 'public'),
    'storage_path'          => env('IMAGES_STORAGE_PATH', 'assets/media'),

    "record_category_icon" => [
        "size" => [
            "thumbnail" => [200, 200],
            "large" => [500, 500]
        ],
        "type" => "single",
        "return" => "thumbnail"
    ],

    "organization_logo" => [
        "size" => [
            "thumbnail" => [200, 200],
            "large" => [500, 500]
        ],
        "type" => "single",
        "return" => "thumbnail"
    ],

    "fund_logo" => [
        "size" => [
            "thumbnail" => [200, 200],
            "large" => [500, 500]
        ],
        "type" => "single",
        "return" => "thumbnail"
    ],

    "office_photo" => [
        "size" => [
            "thumbnail" => [200, 200],
            "large" => [1200, 800]
        ],
        "type" => "single",
        "return" => "thumbnail"
    ],

    "product_photo" => [
        "size" => [
            "thumbnail" => [200, 200],
            "small" => [400, 300],
            "large" => [1200, 800]
        ],
        "type" => "single",
        "return" => "thumbnail"
    ],
];
