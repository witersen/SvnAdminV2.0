<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-26 17:00:09
 * @Description: QQ:1801168257
 */

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
