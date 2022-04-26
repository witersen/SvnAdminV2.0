<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-26 17:00:26
 * @Description: QQ:1801168257
 */

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
