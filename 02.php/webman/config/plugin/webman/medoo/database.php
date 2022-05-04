<?php

/**
 * for MySQL
 */
return [
    'default' => [
        'type' => 'mysql',
        'host' => 'sas2.witersen.com',
        'database' => 'svnadmin',
        'username' => 'svnadmin',
        'password' => 'svnadmin',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_general_ci',
        'port' => 3306,
        'prefix' => '',
        'logging' => false,
        'error' => PDO::ERRMODE_EXCEPTION,
        'option' => [
            PDO::ATTR_CASE => PDO::CASE_NATURAL
        ],
        'command' => [
            'SET SQL_MODE=ANSI_QUOTES'
        ]
    ],
];

/**
 * for SQLite
 */

// $config_svnadmin_svn = config('svnadmin_svn');

// return [
//     'default' => [
//         'type' => 'sqlite',
//         'database' => $config_svnadmin_svn['home_path'] . 'svnadmin.db'
//     ],
// ];
