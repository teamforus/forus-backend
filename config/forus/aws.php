<?php

/**
 * Mapper to indicate which config keys use which aws secret names
 */
return [
    'secret_names' => [
        'database_connections_mysql_password' => env( 'AWS_MYSQL_SECRET_NAME', '')
    ]
];
