<?php

/*
 * 获取数据库连接对象
 */

require_once BASE_PATH . '/extension/Medoo/Medoo.php';
require_once BASE_PATH . '/config/config.php';

use Medoo\Medoo;

class connModel {

    private $database_medoo;

    function __construct() {
        $this->database_medoo = new Medoo([
            'database_type' => DB_TYPE,
            'database_name' => DB_NAME,
            'server' => DB_HOST,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
            'port' => DB_PORT,
        ]);
    }

    function GetConn() {
        return $this->database_medoo;
    }

}
