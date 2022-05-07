<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 14:08:17
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Svnuser as ServiceSvnuser;

class Svnuser extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSvnuser;

    function __construct()
    {
        parent::__construct();

        $this->ServiceSvnuser = new ServiceSvnuser();
    }

    /**
     * 获取全部的SVN用户
     * 
     * 只包含用户名和启用状态
     */
    public function GetAllUserList()
    {
        $result = $this->ServiceSvnuser->GetAllUserList();
        json2($result);
    }

    /**
     * 获取带有分页的SVN用户
     * 
     * 只包含用户名和启用状态
     */
    public function GetUserList()
    {
        $result = $this->ServiceSvnuser->GetUserList();
        json2($result);
    }

    /**
     * 启用SVN用户
     */
    public function EnableUser()
    {
        $result = $this->ServiceSvnuser->EnableUser();
        json2($result);
    }

    /**
     * 禁用SVN用户
     */
    public function DisableUser()
    {
        $result = $this->ServiceSvnuser->DisableUser();
        json2($result);
    }

    /**
     * 修改SVN用户的备注信息
     */
    public function EditUserNote()
    {
        $result = $this->ServiceSvnuser->EditUserNote();
        json2($result);
    }

    /**
     * 新建SVN用户
     */
    public function CreateUser()
    {
        $result = $this->ServiceSvnuser->CreateUser();
        json2($result);
    }

    /**
     * 修改SVN用户的密码
     */
    public function EditUserPass()
    {
        $result = $this->ServiceSvnuser->EditUserPass();
        json2($result);
    }

    /**
     * 删除SVN用户
     */
    public function DelUser()
    {
        $result = $this->ServiceSvnuser->DelUser();
        json2($result);
    }
}
