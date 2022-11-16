<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
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

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServicePersonal = new ServicePersonal($parm);
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

    /**
     * 子管理员修改自己的密码
     */
    public function UpdSubadminUserPass()
    {
        $result = $this->ServicePersonal->UpdSubadminUserPass();
        json2($result);
    }
}
