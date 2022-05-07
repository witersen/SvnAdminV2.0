<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 13:59:41
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Personal as ServicePersonal;

class Personal extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServicePersonal;

    function __construct()
    {
        parent::__construct();

        $this->ServicePersonal = new ServicePersonal();
    }

    /**
     * 管理人员修改自己的账号
     */
    public function EditAdminUserName()
    {
        $result = $this->ServicePersonal->EditAdminUserName();
        json2($result);
    }

    /**
     * 管理人员修改自己的密码
     */
    public function EditAdminUserPass()
    {
        $result = $this->ServicePersonal->EditAdminUserPass();
        json2($result);
    }

    /**
     * SVN用户修改自己的密码
     */
    public function EditSvnUserPass()
    {
        $result = $this->ServicePersonal->EditSvnUserPass();
        json2($result);
    }
}
