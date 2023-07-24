<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Subadmin as ServiceSubadmin;

class Subadmin extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSubadmin;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceSubadmin = new ServiceSubadmin($parm);
    }

    /**
     * 获取子管理员列表
     *
     * @return void
     */
    public function GetSubadminList()
    {
        $result = $this->ServiceSubadmin->GetSubadminList();
        json2($result);
    }

    /**
     * 创建子管理员
     *
     * @return void
     */
    public function CreateSubadmin()
    {
        $result = $this->ServiceSubadmin->CreateSubadmin();
        json2($result);
    }

    /**
     * 删除子管理员
     *
     * @return void
     */
    public function DelSubadmin()
    {
        $result = $this->ServiceSubadmin->DelSubadmin();
        json2($result);
    }

    /**
     * 重置子管理员密码
     *
     * @return void
     */
    public function UpdSubadminPass()
    {
        $result = $this->ServiceSubadmin->UpdSubadminPass();
        json2($result);
    }

    /**
     * 修改子管理员启用状态
     *
     * @return void
     */
    public function UpdSubadminStatus()
    {
        $result = $this->ServiceSubadmin->UpdSubadminStatus();
        json2($result);
    }

    /**
     * 修改子管理员备注信息
     *
     * @return void
     */
    public function UpdSubadminNote()
    {
        $result = $this->ServiceSubadmin->UpdSubadminNote();
        json2($result);
    }

    /**
     * 获取某个子管理员的权限树
     */
    public function GetSubadminTree()
    {
        $result = $this->ServiceSubadmin->GetSubadminTree();
        json2($result);
    }

    /**
     * 修改某个子管理员的权限树
     */
    public function UpdSubadminTree()
    {
        $result = $this->ServiceSubadmin->UpdSubadminTree();
        json2($result);
    }
}
