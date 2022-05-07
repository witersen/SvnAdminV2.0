<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-06 21:37:21
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Safe as ServiceSafe;

class Safe extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSafe;

    function __construct()
    {
        parent::__construct();
        
        $this->ServiceSafe = new ServiceSafe();
    }
}
