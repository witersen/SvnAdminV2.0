<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 13:05:54
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Svnaliase as ServiceSvnaliase;

class Svnaliase extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSvnaliase;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceSvnaliase = new ServiceSvnaliase($parm);
    }

    /**
     * 获取全部的SVN别名
     */
    public function GetAliaseList()
    {
        $result = $this->ServiceSvnaliase->GetAliaseList();
        json2($result);
    }
}
