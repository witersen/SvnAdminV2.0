<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 14:04:33
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Svngroup as ServiceSvngroup;

class Svngroup extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSvngroup;

    function __construct()
    {
        parent::__construct();

        $this->ServiceSvngroup = new ServiceSvngroup();
    }

    /**
     * 获取所有的分组列表
     */
    public function GetAllGroupList()
    {
        $result = $this->ServiceSvngroup->GetAllGroupList();
        json2($result);
    }

    /**
     * 获取带有分页的分组列表
     */
    public function GetGroupList()
    {
        $result = $this->ServiceSvngroup->GetGroupList();
        json2($result);
    }

    /**
     * 编辑分组备注信息
     */
    public function EditGroupNote()
    {
        $result = $this->ServiceSvngroup->EditGroupNote();
        json2($result);
    }

    /**
     * 新建SVN分组
     */
    public function CreateGroup()
    {
        $result = $this->ServiceSvngroup->CreateGroup();
        json2($result);
    }

    /**
     * 删除SVN分组
     */
    public function DelGroup()
    {
        $result = $this->ServiceSvngroup->DelGroup();
        json2($result);
    }

    /**
     * 修改SVN分组的名称
     */
    public function EditGroupName()
    {
        $result = $this->ServiceSvngroup->EditGroupName();
        json2($result);
    }

    /**
     * 获取SVN分组的用户成员和分组成员
     */
    public function GetGroupMember()
    {
        $result = $this->ServiceSvngroup->GetGroupMember();
        json2($result);
    }

    /**
     * 将用户添加为SVN分组的成员
     */
    public function GroupAddUser()
    {
        $result = $this->ServiceSvngroup->GroupAddUser();
        json2($result);
    }

    /**
     * 将用户从SVN分组的成员移除
     */
    public function GroupRemoveUser()
    {
        $result = $this->ServiceSvngroup->GroupRemoveUser();
        json2($result);
    }

    /**
     * 将分组添加为SVN分组的成员
     */
    public function GroupAddGroup()
    {
        $result = $this->ServiceSvngroup->GroupAddGroup();
        json2($result);
    }

    /**
     * 将分组从SVN分组的成员移除
     */
    public function GroupRemoveGroup()
    {
        $result = $this->ServiceSvngroup->GroupRemoveGroup();
        json2($result);
    }
}
