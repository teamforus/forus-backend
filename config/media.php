<?php

return [
    'filesystem_driver'     => env('IMAGES_STORAGE_DRIVER', 'public'),
    'storage_path'          => env('IMAGES_STORAGE_PATH', 'assets/media'),

    'sizes' => [
        "record_category_icon" => [
            "size" => [
                "thumbnail" => [200, 200],
                "large" => [500, 500]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1,
        ],

        "organization_logo" => [
            "size" => [
                "thumbnail" => [200, 200],
                "large" => [500, 500]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1,
        ],

        "fund_logo" => [
            "size" => [
                "thumbnail" => [200, 200],
                "large" => [500, 500]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1,
        ],

        "office_photo" => [
            "size" => [
                "thumbnail" => [200, 200],
                "large" => [1200, 800]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1.3333,
        ],

        "product_photo" => [
            "size" => [
                "thumbnail" => [200, 200],
                "small" => [400, 300],
                "large" => [1200, 800]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1.3333,
        ],
    ]
];
