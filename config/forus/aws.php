<?php

/**
 * Mapper to indicate which config keys use which aws secret names
 */
return [
    'secret_names' => [
        'database.connections.mysql.password' => 'prod/db/mysql'
    ]
];
