<?php

return [
    'filesystem_driver'     => env('IMAGES_STORAGE_DRIVER', 'public'),
    'storage_path'          => env('IMAGES_STORAGE_PATH', 'media'),

    'sizes' => [
        "record_category_icon" => [
            "size" => [
                "thumbnail" => [200, 200, false],
                "large" => [500, 500, false],
                "original" => [1000, 1000, true]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1,
        ],

        "organization_logo" => [
            "size" => [
                "thumbnail" => [200, 200, false],
                "large" => [500, 500, false],
                "original" => [1000, 1000, true]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1,
        ],

        "fund_logo" => [
            "size" => [
                "thumbnail" => [200, 200, false],
                "large" => [500, 500, false],
                "original" => [1000, 1000, true]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1,
        ],

        "office_photo" => [
            "size" => [
                "thumbnail" => [200, 200, false],
                "large" => [1200, 800, false],
                "original" => [1600, 1600, true]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1.3333,
        ],

        "product_photo" => [
            "size" => [
                "thumbnail" => [200, 200, false],
                "small" => [400, 300, false],
                "large" => [1200, 800, false],
                "original" => [1600, 1600, true]
            ],
            "type" => "single",
            "return" => "thumbnail",
            "aspect_ratio" => 1.3333,
        ],
    ]
];
