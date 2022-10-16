<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 14:09:40
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
    private $ServiceSuadmin;

    function __construct()
    {
        parent::__construct();

        $this->ServiceSubadmin = new ServiceSubadmin();
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
}
