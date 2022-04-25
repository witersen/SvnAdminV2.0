<?php

//declare(strict_types=1);

/*
 * 获取数据库连接对象
 */
require_once BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php';

use Medoo\Medoo;

class conn
{

    private $database_medoo;

    function __construct()
    {
        $this->database_medoo = new Medoo(unserialize(DATABASE_ENABLE));
    }

    function GetConn()
    {
        return $this->database_medoo;
    }
}
