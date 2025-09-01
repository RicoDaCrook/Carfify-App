<?php
/**
 * Development Configuration
 */

return [
    'app' => [
        'debug' => true,
        'log_level' => 'debug'
    ],
    'database' => [
        'host' => 'localhost',
        'database' => 'carfify_dev',
        'username' => 'dev_user',
        'password' => 'dev_password'
    ],
    'cache' => [
        'enabled' => false,
        'driver' => 'file'
    ]
];