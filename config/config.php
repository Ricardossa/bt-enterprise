<?php

declare(strict_types=1);

return [

    'app' => [
        'name' => 'BT Queue Enterprise',
        'version' => '4.0.0',
        'timezone' => 'America/Bahia',
        'debug' => false
    ],

    'database' => [
        'driver' => 'sqlite',
        'path' => dirname(__DIR__) . '/database/banco.db'
    ],

    'license' => [
        'offline_days' => 7
    ],

    'sync' => [
        'enabled' => true,
        'endpoint' => '',
        'token' => ''
    ],

    'logs' => [
        'path' => dirname(__DIR__) . '/logs'
    ]

];
