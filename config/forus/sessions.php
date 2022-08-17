<?php

return [
    'track_guests' => env('SESSIONS_TRACK_GUESTS', false),
    'geo_ip_db_path' => env('SESSIONS_GEO_IP_DB_PATH', 'geo-ip-db/GeoLite2-City.mmdb'),
    'geo_ip_enabled' => env('SESSIONS_GEO_IP_ENABLED', false),
    'user_agent_enabled' => env('SESSIONS_USER_AGENT_ENABLED', true),

    'app_expire_time' => [
        'unit' => 'years',
        'value' => env('SESSION_EXPIRE_APP_YEARS', 2),
    ],
    'webshop_expire_time' => [
        'unit' => 'minutes',
        'value' => env('SESSION_EXPIRE_WEBSHOP_MINUTES', 15),
    ],
    'dashboard_expire_time' => [
        'unit' => 'months',
        'value' => env('SESSION_EXPIRE_APP_MONTHS', 3),
    ],
];