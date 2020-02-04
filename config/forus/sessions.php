<?php

return [
    'track_guests'          => false,
    'geo_ip_db_path'        => env('SESSIONS_GEO_IP_DB_PATH', 'geo-ip-db/GeoLite2-City.mmdb'),
    'geo_ip_enabled'        => env('SESSIONS_GEO_IP_ENABLED', false),

    'user_agent_enabled'    => env('SESSIONS_USER_AGENT_ENABLED', true)
];