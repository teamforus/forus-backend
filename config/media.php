<?php

return [
    'filesystem_driver'     => env('IMAGES_STORAGE_DRIVER', 'public'),
    'storage_path'          => env('IMAGES_STORAGE_PATH', 'media'),

    'queue_name'            => env('IMAGES_QUEUE_NAME', 'media'),
    'use_queue'             => env('IMAGES_CONVERT_IN_QUEUE', false),
    'calc_dominant_color'   => env('IMAGES_CALC_DOMINANT_COLOR', false),
];
