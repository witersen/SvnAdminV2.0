<?php

/*
 * MySQL配置信息
 */
define('MYSQL_CONFIG', serialize([
        'database_type' => 'mysql',
        'database_name' => 'svnadmin',
        'server' => 'sas2.witersen.com',
        'username' => 'svnadmin',
        'password' => 'svnadmin',
        'charset' => 'utf-8',
        'port' => 3306
    ]));

/**
 * SQLite配置信息
 */
define('SQLITE_CONFIG', serialize([
    'database_type' => 'sqlite',
    'database_file' => CONFIG_PATH . '/svnadmin.db',
]));

/**
 * 启用信息
 */
define('DATABASE_ENABLE', MYSQL_CONFIG);
