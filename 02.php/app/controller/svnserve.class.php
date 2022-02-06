<?php

declare(strict_types=1);

/*
 * 与svn服务相关的方法的封装
 * 
 * 相关目录：
 * 1、conf目录地址：/www/svn/conf
 * 2、repository地址：/www/svn/repository
 * 3、/etc/sysconfig/svnserve 文件内容指定了SVN服务的repository目录 
 * 4、/etc/subversion 
 * 5、SVN项目部署目录：自定义 文件会自动保存在自定义的部署目录
 * 6、/var/svn 为subversive的默认仓库目录
 * 7、如果在本地检出时，http://domain/仓库名称/format 无法访问 权限不够 需要关闭Linux系统的selinux
 */

class Svnserve extends Controller
{
    private $Config;
    private $svn_repository_path;
    private $server_domain;
    private $svn_protocol;
    private $svn_port;
    private $http_port;
    private $server_ip;
    private $Firewall;
    private $System;
    private $Mail;
    private $Clientinfo;

    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
        $this->Config = new Config();

        $this->Firewall = new Firewall();

        $this->System = new System();

        $this->Mail = new Mail();

        $this->Clientinfo = new Clientinfo();

        $this->svn_repository_path = SVN_REPOSITORY_PATH;
        $this->server_domain = SERVER_DOMAIN;
        $this->server_ip = SERVER_IP;
        $this->svn_protocol = SVN_PROTOCOL;
        $this->svn_port = SVN_PORT;
        $this->http_port = HTTP_PORT;
    }


    /**
     * 添加仓库用户
     */
    function RepAddUser($requestPayload)
    {
        $userName = trim($requestPayload['userName']);
        $userPass = $requestPayload['userPass'];
        $userPass2 = $requestPayload['userPass2'];

        if (empty($userName) ||  $userPass != $userPass2) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        if (!FunIsValidRepUser($userName)) {
            $data['status'] = 0;
            $data['message'] = 'SVN用户名只能包含字母、数字、破折号、下划线、点';
            return $data;
        }

        $status =  FunAddSvnUser($this->globalPasswdContent, $userName, $userPass);
        if ($status == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[users]标识';
            return $data;
        }
        if ($status == '1') {
            $data['status'] = 0;
            $data['message'] = '用户已存在';
            return $data;
        }
        FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_PASSWD);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 获取仓库用户列表
     */
    function RepGetUserList($requestPayload)
    {
        $pageSize = $requestPayload['pageSize'];
        $currentPage = $requestPayload['currentPage'];

        if (empty($pageSize) || empty($currentPage)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        //检查svn状态
        $svn_check_status = $this->CheckSvnserveStatus();
        if ($svn_check_status['status'] == 0) {
            $data['status'] = 0;
            $data['message'] = $svn_check_status['message'];
            return $data;
        }

        $userPassList = FunGetSvnUserPassList($this->globalPasswdContent);

        $total = count($userPassList);

        $begin = $pageSize * ($currentPage - 1);

        $userPassList = array_splice($userPassList, $begin, $pageSize);

        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = $userPassList;
        $data['total'] = $total;
        return $data;
    }

    /**
     * 编辑仓库用户信息
     */
    function RepEditUser($requestPayload)
    {
        $edit_username = $requestPayload['edit_username'];
        $edit_password = $requestPayload['edit_password'];
        $edit_password2 = $requestPayload['edit_password2'];

        if (empty(trim($edit_username)) ||  $edit_password != $edit_password2) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        $status = FunUpdSvnUserPass($this->globalPasswdContent, $edit_username, $edit_password);
        if ($status == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[users]标识';
            return $data;
        }
        if ($status == '1') {
            $data['status'] = 0;
            $data['message'] = '用户不存在';
            return $data;
        }
        FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_PASSWD);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 删除仓库用户
     */
    function RepUserDel($requestPayload)
    {
        $del_username = $requestPayload['del_username'];

        if (empty($del_username)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        $status = FunDelSvnUserPasswd($this->globalPasswdContent, $del_username);
        if ($status == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[users]标识';
            return $data;
        }
        if ($status == '1') {
            $data['status'] = 0;
            $data['message'] = '用户不存在';
            return $data;
        }
        FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_PASSWD);

        $status = FunDelUserAuthz($this->globalAuthzContent, $del_username);
        if ($status == '1') {
            $data['status'] = 0;
            $data['message'] = '用户不存在';
            return $data;
        }
        FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_AUTHZ);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 启用SVN用户
     */
    function RepEnabledUser($requestPayload)
    {
        $userName = $requestPayload['userName'];

        if (empty($userName)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        $status = FunEnabledUser($this->globalPasswdContent, $userName);

        if ($status == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[users]标识';
            return $data;
        }
        if ($status == '1') {
            $data['status'] = 0;
            $data['message'] = '要启用的用户不存在';
            return $data;
        }

        FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_PASSWD);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 禁用SVN用户
     */
    function RepDisabledUser($requestPayload)
    {
        $userName = $requestPayload['userName'];

        if (empty($userName)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        $status = FunDisabledUser($this->globalPasswdContent, $userName);

        if ($status == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[users]标识';
            return $data;
        }
        if ($status == '1') {
            $data['status'] = 0;
            $data['message'] = '要禁用的用户不存在';
            return $data;
        }

        FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_PASSWD);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 添加仓库分组
     */
    function RepAddGroup($requestPayload)
    {
        $groupName = trim($requestPayload['groupName']);

        if (empty($groupName)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        if (!FunIsValidRepGroup($groupName)) {
            $data['status'] = 0;
            $data['message'] = 'SVN组名称只能包含字母、数字、破折号、下划线、点';
            return $data;
        }

        $status = FunAddSvnGroup($this->globalAuthzContent, $groupName);

        if ($status == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[groups]标识';
            return $data;
        }
        if ($status == '1') {
            $data['status'] = 0;
            $data['message'] = '分组已存在';
            return $data;
        }
        FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_AUTHZ);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 获取仓库分组列表
     */
    function RepGetGroupList($requestPayload)
    {
        $pageSize = $requestPayload['pageSize'];
        $currentPage = $requestPayload['currentPage'];

        if (empty($pageSize) || empty($currentPage)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        //检查svn状态
        $svn_check_status = $this->CheckSvnserveStatus();
        if ($svn_check_status['status'] == 0) {
            $data['status'] = 0;
            $data['message'] = $svn_check_status['message'];
            return $data;
        }

        // $groupList = FunGetSvnGroupList($this->globalAuthzContent);
        $groupUserList = FunGetSvnGroupUserAndGroupList($this->globalAuthzContent);

        if ($groupUserList == '0') {
            $data['status'] = 0;
            $data['message'] = '文件格式错误(不存在[groups]标识)';
            return $data;
        }

        $total = count($groupUserList);

        $begin = $pageSize * ($currentPage - 1);

        //array_splice会将下标自动转换 使用要注意
        $groupUserList = array_splice($groupUserList, $begin, $pageSize);

        // return $groupUserList;

        // $info = [];
        // foreach ($groupList as $key => $value) {
        //     $usercount = count((array_column($groupUserList, 'include', 'groupName'))[$value]['users']);
        //     if ($usercount == 1) {
        //         $usercount = trim((array_column($groupUserList, 'include', 'groupName'))[$value]['users'][0]) == '' ? 0 : 1;
        //     } else {
        //         $usercount = count((array_column($groupUserList, 'include', 'groupName'))[$value]['users']);
        //     }
        //     array_push($info, array(
        //         'groupName' => $value,
        //         'includeUserCount' => $usercount,
        //         'includeGroupCount'=>''
        //     ));
        // }

        foreach ($groupUserList as $key => $value) {
            $groupUserList[$key]['includeUserCount'] = count($value['include']['users']);
            $groupUserList[$key]['includeGroupCount'] = count($value['include']['groups']);
            unset($groupUserList[$key]['include']);
        }


        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = $groupUserList;
        $data['total'] = $total;
        return $data;
    }

    /**
     * 编辑仓库分组信息
     */
    function RepEditGroup($requestPayload)
    {
        $oldGroup = trim($requestPayload['oldGroup']);
        $newGroup = trim($requestPayload['newGroup']);

        if (empty($oldGroup) || empty($newGroup)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        if (!FunIsValidRepGroup($newGroup)) {
            $data['status'] = 0;
            $data['message'] = 'SVN组名称只能包含字母、数字、破折号、下划线、点';
            return $data;
        }

        if ($oldGroup == $newGroup) {
            $data['status'] = 0;
            $data['message'] = '无修改';
            return $data;
        }

        $status = FunUpdSvnGroup($this->globalAuthzContent, $oldGroup, $newGroup);
        FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_AUTHZ);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 删除仓库分组
     */
    function RepGroupDel($requestPayload)
    {
        $groupName = $requestPayload['del_groupname'];

        if (empty($groupName)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        $status = FunDelSvnGroup($this->globalAuthzContent, $groupName);
        FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_AUTHZ);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 获取分组包含的用户列表
     */
    function RepGetGroupUserList($requestPayload)
    {
        $groupName = $requestPayload['groupName'];

        if (empty($groupName)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        $status1 = FunGetSvnUserListByGroup($this->globalAuthzContent, $groupName);
        if ($status1 == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[groups]标识';
            return $data;
        }
        if ($status1 == '1') {
            $data['status'] = 0;
            $data['message'] = '分组不存在';
            return $data;
        }
        $status2 = FunGetSvnUserList($this->globalPasswdContent);
        if ($status2 == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[users]标识';
            return $data;
        }
        $userList = array_column($status2, 'userName');
        $list = [];
        foreach ($userList as $value) {
            if (in_array($value, $status1)) {
                array_push($list, array(
                    'username' => $value,
                    'status' => 'in'
                ));
            } else {
                array_push($list, array(
                    'username' => $value,
                    'status' => 'no'
                ));
            }
        }
        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = $list;
        return $data;
    }

    /**
     * 获取分组包含的分组列表
     */
    function RepGetGroupGroupList($requestPayload)
    {
        $groupName = $requestPayload['groupName'];

        if (empty($groupName)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        $status1 = FunGetSvnGroupListByGroup($this->globalAuthzContent, $groupName);
        if ($status1 == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[groups]标识';
            return $data;
        }
        if ($status1 == '1') {
            $data['status'] = 0;
            $data['message'] = '分组不存在';
            return $data;
        }
        $status2 = FunGetSvnGroupList($this->globalAuthzContent);
        if ($status2 == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[groups]标识';
            return $data;
        }
        $list = [];
        foreach ($status2 as $value) {
            if (in_array($value, $status1)) {
                array_push($list, array(
                    'groupName' => $value,
                    'status' => 'in'
                ));
            } else {
                array_push($list, array(
                    'groupName' => $value,
                    'status' => 'no'
                ));
            }
        }
        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = $list;
        return $data;
    }

    /**
     * 设置仓库分组的用户
     */
    function RepSetGroupUserList($requestPayload)
    {
        $group_name = trim($requestPayload['group_name']);
        $this_account_list = $requestPayload['this_account_list'];

        if (empty($group_name) || empty($this_account_list)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        $authzContent = $this->globalAuthzContent;
        foreach ($this_account_list as $key => $value) {
            if ($value['status'] == 'no') {
                $status1 = FunDelSvnGroupUser($authzContent, $group_name, $value['username']);
                if ($status1 == '0') {
                    $data['status'] = 0;
                    $data['message'] = '文件中不存在[groups]标识';
                    return $data;
                } elseif ($status1 == '1') {
                    $data['status'] = 0;
                    $data['message'] = '用户组不存在';
                    return $data;
                } elseif ($status1 == '2') {
                } else {
                    $authzContent = $status1;
                }
            } else if ($value['status'] == 'in') {
                $status2 = FunAddSvnGroupUser($authzContent, $group_name, $value['username']);
                if ($status2 == '0') {
                    $data['status'] = 0;
                    $data['message'] = '文件中不存在[groups]标识';
                    return $data;
                } elseif ($status2 == '1') {
                    $data['status'] = 0;
                    $data['message'] = '用户组不存在';
                    return $data;
                } elseif ($status2 == '2') {
                } else {
                    $authzContent = $status2;
                }
            }
        }

        FunRequestReplyExec('echo \'' . $authzContent . '\' > ' . SVN_SERVER_AUTHZ);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 设置仓库分组的分组
     */
    function RepSetGroupGroupList($requestPayload)
    {
        $parentGroupName = trim($requestPayload['groupName']);
        $thisAccountList = $requestPayload['thisAccountList'];

        if (empty($parentGroupName) || empty($thisAccountList)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        $authzContent = $this->globalAuthzContent;
        foreach ($thisAccountList as $key => $value) {
            if ($value['status'] == 'no') {
                $status1 = FunDelSvnGroupGroup($authzContent, $parentGroupName, $value['groupName']);
                if ($status1 == '0') {
                    $data['status'] = 0;
                    $data['message'] = '文件中不存在[groups]标识';
                    return $data;
                } elseif ($status1 == '1') {
                    $data['status'] = 0;
                    $data['message'] = '用户组不存在';
                    return $data;
                } elseif ($status1 == '2') {
                } else {
                    $authzContent = $status1;
                }
            } else if ($value['status'] == 'in') {
                $status2 = FunAddSvnGroupGroup($authzContent, $parentGroupName, $value['groupName']);
                if ($status2 == '0') {
                    $data['status'] = 0;
                    $data['message'] = '文件中不存在[groups]标识';
                    return $data;
                } elseif ($status2 == '1') {
                    $data['status'] = 0;
                    $data['message'] = '用户组不存在';
                    return $data;
                } elseif ($status2 == '2') {
                } elseif ($status2 == '3') {
                    $data['status'] = 0;
                    $data['message'] = '存在分组循环嵌套的情况';
                    return $data;
                } else {
                    //获取分组所在的所有分组
                    $groupGroupList = $this->GetSvnGroupAllGroupList($parentGroupName);

                    if (in_array($value['groupName'], $groupGroupList)) {
                        $data['status'] = 0;
                        $data['message'] = '存在分组循环嵌套的情况';
                        return $data;
                    }

                    $authzContent = $status2;
                }
            }
        }

        FunRequestReplyExec('echo \'' . $authzContent . '\' > ' . SVN_SERVER_AUTHZ);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 获取用户所在的所有分组
     * 
     * 包括直接包含关系 如
     * group1=user1
     * 
     * 和间接包含关系 如
     * group1=user1
     * group2=@group1
     * group3=@group2
     * group4=@group3
     */
    private function GetSvnUserAllGroupList($userName)
    {
        $authzContent = $this->globalAuthzContent;

        //所有的分组列表
        $allGroupList = FunGetSvnGroupList($authzContent);

        //用户所在的分组列表
        $userGroupList = FunGetSvnUserGroupList($authzContent, $userName);

        //剩余的分组列表
        $leftGroupList = array_diff($allGroupList, $userGroupList);

        //循环匹配 直到匹配到与该用户相关的有权限的用户组为止
        loop:
        $userGroupListBack = $userGroupList;
        foreach ($userGroupList as $group1) {
            $newList = FunGetSvnGroupGroupList($authzContent, $group1);
            foreach ($leftGroupList as $group2) {
                if (in_array($group2, $newList)) {
                    array_push($userGroupList, $group2);
                    unset($leftGroupList[array_search($group2, $leftGroupList)]);
                }
            }
        }
        if ($userGroupList != $userGroupListBack) {
            goto loop;
        }

        return $userGroupList;
    }

    /**
     * 获取分组所在的所有分组
     * 
     * 包括直接包含关系 如
     * group2=@group1
     * 
     * 和间接包含关系 如
     * group2=@group1
     * group3=@group2
     * group4=@group3
     */
    private function GetSvnGroupAllGroupList($groupName)
    {
        $parentGroupName = $groupName;

        $authzContent = $this->globalAuthzContent;

        //所有的分组列表
        $allGroupList = FunGetSvnGroupList($authzContent);

        //分组所在的分组列表 
        $groupGroupList = FunGetSvnGroupGroupList($authzContent, $parentGroupName);

        //剩余的分组列表
        $leftGroupList = array_diff($allGroupList, $groupGroupList);

        //循环匹配
        loop:
        $userGroupListBack = $groupGroupList;
        foreach ($groupGroupList as $group1) {
            $newList = FunGetSvnGroupGroupList($authzContent, $group1);
            foreach ($leftGroupList as $group2) {
                if (in_array($group2, $newList)) {
                    array_push($groupGroupList, $group2);
                    unset($leftGroupList[array_search($group2, $leftGroupList)]);
                }
            }
        }
        if ($groupGroupList != $userGroupListBack) {
            goto loop;
        }

        return $groupGroupList;
    }

    //设置仓库的hooks
    function SetRepHooks($requestPayload)
    {
        $repository_name = trim($requestPayload['repository_name']);
        $hooks_type_list = $requestPayload['hooks_type_list'];

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name . '/' . 'hooks')) {
            $data['status'] = 0;
            $data['message'] = '仓库不存在或文件损坏';
            return $data;
        }

        foreach ($hooks_type_list as $key => $value) {
            file_put_contents(SVN_REPOSITORY_PATH . '/' . $repository_name . '/' . 'hooks' . '/' . $value['value'], $value["shell"]);
        }

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    //获取仓库的hooks
    function GetRepositoryHooks($requestPayload)
    {
        $repository_name = trim($requestPayload['repository_name']);

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        if (empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }
        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name . '/' . 'hooks')) {
            $data['status'] = 0;
            $data['message'] = '仓库不存在或文件损坏';
            return $data;
        }
        $hooks_type_list = array(
            "start-commit" => array(
                "value" => "start-commit",
                "label" => "start-commit---事务创建前",
                "shell" => ""
            ),
            "pre-commit" => array(
                "value" => "pre-commit",
                "label" => "pre-commit---事务提交前",
                "shell" => ""
            ),
            "post-commit" => array(
                "value" => "post-commit",
                "label" => "post-commit---事务提交后",
                "shell" => ""
            ),
            "pre-lock" => array(
                "value" => "pre-lock",
                "label" => "pre-lock---锁定文件前",
                "shell" => ""
            ),
            "post-lock" => array(
                "value" => "post-lock",
                "label" => "post-lock---锁定文件后",
                "shell" => ""
            ),
            "pre-unlock" => array(
                "value" => "pre-unlock",
                "label" => "pre-unlock---解锁文件前",
                "shell" => ""
            ),
            "post-unlock" => array(
                "value" => "post-unlock",
                "label" => "post-unlock---解锁文件后",
                "shell" => ""
            ),
            "pre-revprop-change" => array(
                "value" => "pre-revprop-change",
                "label" => "pre-revprop-change---修改修订版属性前",
                "shell" => ""
            ),
            "post-revprop-change" => array(
                "value" => "post-revprop-change",
                "label" => "post-revprop-change---修改修订版属性后",
                "shell" => ""
            ),
        );
        $hooks_file_list = array(
            "start-commit",
            "pre-commit",
            "post-commit",
            "pre-lock",
            "post-lock",
            "pre-unlock",
            "post-unlock",
            "pre-revprop-change",
            "post-revprop-change"
        );
        $file_arr = scandir(SVN_REPOSITORY_PATH . '/' . $repository_name . '/' . 'hooks');
        foreach ($file_arr as $file_item) {
            if ($file_item != '.' && $file_item != '..') {
                if (in_array($file_item, $hooks_file_list)) {
                    $hooks_type_list[$file_item]['shell'] = file_get_contents(SVN_REPOSITORY_PATH . '/' . $repository_name . '/' . 'hooks' . '/' . $file_item);
                }
            }
        }
        $data['status'] = 1;
        $data['data'] = $hooks_type_list;
        $data['message'] = '成功';
        return $data;
    }

    //系统首页 获取概览情况
    function GetGailan($requestPayload)
    {
        $resultlist = array(
            'os_type' => "", //操作系统类型
            'os_runtime' => "", //系统运行天数
            'repository_count' => "", //svn仓库数量
            'admin_count' => "", //系统管理员数量
            'user_count' => "", //普通用户数量
        );

        //操作系统类型
        $resultlist['os_type'] = file('/etc/os-release');
        if (file_exists('/etc/redhat-release')) {
            $resultlist['os_type'] = "CentOS";
        } elseif (file_exists('etc/lsb-release')) {
            $resultlist['os_type'] = "Ubuntu";
        } else {
            $resultlist['os_type'] = "-";
        }
        //服务器运行天数
        $info = trim(explode(" ", file_get_contents('/proc/uptime'))[0]); //系统自启动开始的秒数
        $resultlist['os_runtime'] = floor($info / 60 / 60 / 24);
        //svn仓库数量
        $svn_check_status = $this->CheckSvnserveStatus();
        $resultlist['repository_count'] = 0;
        if ($svn_check_status['code'] == '01' || $svn_check_status['code'] == '11') {
            $i = 0;
            $file_arr = scandir(SVN_REPOSITORY_PATH);
            foreach ($file_arr as $file_item) {
                if ($file_item != '.' && $file_item != '..') {
                    if (is_dir(SVN_REPOSITORY_PATH . '/' . $file_item)) {
                        $file_arr2 = scandir(SVN_REPOSITORY_PATH . '/' . $file_item);
                        foreach ($file_arr2 as $file_item2) {
                            if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                                $i++;
                                break;
                            }
                        }
                    }
                }
            }
            $resultlist['repository_count'] = $i;
        }
        //管理员数量
        $resultlist['super_count'] = 1;
        //SVN用户数量
        $resultlist['user_count'] = count(array_column(FunGetSvnUserList($this->globalPasswdContent), 'userName'));

        $data['status'] = 1;
        $data['data'] = $resultlist;
        $data['message'] = '成功';
        return $data;
    }

    //安装svnserve服务
    function Install($requestPayload)
    {
        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        //创建svn仓库父目录
        FunRequestReplyExec('mkdir -p ' . SVN_REPOSITORY_PATH);

        //创建数据备份目录
        FunRequestReplyExec('mkdir -p ' . BACKUP_PATH);

        //创建日志目录
        FunRequestReplyExec('mkdir -p ' . LOG_PATH);

        //通过ps auxf|grep -v "grep"|grep svnserve和判断文件/usr/bin/svnserve是否存在这两方面来同时判断 如果没有安装过则进行安装
        $info = FunRequestReplyExec('ps auxf|grep -v "grep"|grep svnserve');
        if ($info == ISNULL && !file_exists('/usr/bin/svnserve')) {
            //创建文件 svnserve.conf  并写入内容
            FunRequestReplyExec('touch ' . SVN_SERVER_CONF);
            FunRequestReplyExec('echo \'' . file_get_contents(BASE_PATH . '/data/templete/svnserve.conf') . '\' > ' . SVN_SERVER_CONF);

            //创建文件 passwd
            FunRequestReplyExec('touch ' . SVN_SERVER_PASSWD);
            FunRequestReplyExec('echo \'' . file_get_contents(BASE_PATH . '/data/templete/passwd') . '\' > ' . SVN_SERVER_PASSWD);

            //创建文件 authz
            FunRequestReplyExec('touch ' . SVN_SERVER_AUTHZ);
            FunRequestReplyExec('echo \'' . file_get_contents(BASE_PATH . '/data/templete/authz') . '\' > ' . SVN_SERVER_AUTHZ);

            //yum 方式安装 subversion
            FunRequestReplyExec("yum install -y subversion");

            //通常cp的别名为cp -i ，取消别名
            FunRequestReplyExec("alias cp='cp'");

            //备份文件
            FunRequestReplyExec('cp -f /etc/sysconfig/svnserve /etc/sysconfig/svnserve.bak');

            //更改存储库位置 将配置文件/etc/sysconfig/svnserve中的/var/svn/更换为svn仓库目录
            //增加启动参数 指定所有仓库被一个配置文件管理
            FunRequestReplyExec('sed -i \'s/\/var\/svn/ ' . str_replace('/', '\/', SVN_REPOSITORY_PATH) . ' --config-file ' . str_replace('/', '\/', SVN_SERVER_CONF) . '/g\'' . ' /etc/sysconfig/svnserve');

            //设置存储密码选项 将以下内容写入文件/etc/subversion/servers servers文件不存在则创建
            /**
             * [groups]
             * [global]
             * store-plaintext-passwords = yes
             */
            FunRequestReplyExec("touch /etc/subversion/servers");
            // $con = "[groups]\n[global]\nstore-plaintext-passwords = yes\n";
            FunRequestReplyExec('echo \'' . file_get_contents(BASE_PATH . '/data/templete/servers') . '\' > /etc/subversion/servers');

            //加入开机启动项
            FunRequestReplyExec("systemctl enable svnserve.service");

            //启动
            FunRequestReplyExec("systemctl start svnserve.service");

            //将svn和http默认端口加入防火墙
            $this->Firewall->SetFirewallPolicy(["port" => SVN_PORT, "type" => "add"]);
            $this->Firewall->SetFirewallPolicy(["port" => HTTP_PORT, "type" => "add"]);

            //临时关闭selinux
            FunRequestReplyExec('setenforce 0');

            //永久关闭selinux
            FunRequestReplyExec("sed -i 's/SELINUX=enforcing/SELINUX=disabled/g' /etc/selinux/config");

            $data['status'] = 1;
            $data['message'] = '成功';
            return $data;
        } else {
            $data['status'] = 1;
            $data['message'] = 'Subversion已存在';
            return $data;
        }
    }

    //卸载svnserve服务
    function UnInstall($requestPayload)
    {
        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        FunRequestReplyExec('systemctl stop svnserve');
        FunRequestReplyExec('systemctl disable svnserve');
        FunRequestReplyExec('yum remove -y subversion');
        FunRequestReplyExec('rm -f /etc/subversion/servers');
        FunRequestReplyExec('rm -rf /etc/subversion');
        FunRequestReplyExec('rm -rf /usr/bin/svnserve');

        //清除yum缓存
        FunRequestReplyExec('yum clean all');

        //is_dir的结果会被缓存，所以需要清除缓存
        clearstatcache();

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    //修复svn服务
    function Repaire($requestPayload)
    {
    }

    /**
     * 获取仓库列表
     */
    function GetRepositoryList($requestPayload)
    {
        $pageSize = $requestPayload['pageSize'];
        $currentPage = $requestPayload['currentPage'];

        if (empty($pageSize) || empty($currentPage) || $pageSize == 0) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        //检查svn状态
        $svn_check_status = $this->CheckSvnserveStatus();
        if ($svn_check_status['status'] == 0) {
            $data['status'] = 0;
            $data['message'] =  $svn_check_status['message'];
            return $data;
        }

        $repArray = FunGetRepList();

        $authzContent = $this->globalAuthzContent;

        $authzContentBackup = $authzContent;

        //检查是否存在没有写入配置文件的仓库字段
        foreach ($repArray as $key => $value) {
            $status = FunSetRepAuthz($authzContent, $value['repName'], '/');
            if ($status != '1') {
                $authzContent = $status;
            }
        }

        //写入
        if ($authzContentBackup != $authzContent) {
            FunRequestReplyExec('echo \'' . $authzContent . '\' > ' . SVN_SERVER_AUTHZ);
        }

        if ($this->globalUserRoleId == 2) {
            //获取用户所在的所有分组
            $userGroupList = $this->GetSvnUserAllGroupList($this->globalUserName);

            $allRepList = [];

            //获取分组有权限的仓库列表
            foreach ($userGroupList as $value) {
                $allRepList = array_merge($allRepList, FunGetGroupPriRepListWithoutPri($authzContent, $value));
            }

            //获取用户有权限的仓库列表
            $allRepList = array_merge($allRepList, FunGetUserPriRepListWithoutPri($authzContent, $this->globalUserName));

            //获取所有用户有权限的仓库列表
            $allRepList = array_merge($allRepList, FunGetAllHavePriRepListWithoutPri($authzContent));

            //处理
            foreach ($repArray as $key => $value) {
                if (!in_array($value['repName'], $allRepList)) {
                    unset($repArray[$key]);
                }
            }
        }

        $begin = $pageSize * ($currentPage - 1);

        $total =  count($repArray);

        //array_splice会将下标自动转换 使用要注意
        $list = array_splice($repArray, $begin, (int)$pageSize);

        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = $list;
        $data['total'] = $total;
        return $data;
    }

    //项目管理 按钮 添加svn仓库   包括项目标题
    function AddRepository($requestPayload)
    {
        $repository_name = $requestPayload['repository_name'];

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        $svn_check_status = $this->CheckSvnserveStatus();
        if ($svn_check_status['status'] == 0) {
            $data['status'] = 0;
            $data['message'] = $svn_check_status['message'];
            return $data;
        }

        //只能包含字母、数字、破折号、下划线或点字符，不能以句点开头或结尾
        if (!FunIsValidRepName($repository_name)) {
            $data['status'] = 0;
            $data['message'] = 'SVN仓库名称只能包含字母、数字、破折号、下划线、点，不能以点开头或结尾';
            return $data;
        }

        //判断仓库是否存在
        if (is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '仓库已经存在';
            return $data;
        }

        //创建仓库
        //解决创建中文仓库乱码问题
        FunRequestReplyExec('export LC_CTYPE=en_US.UTF-8 &&  svnadmin create ' . SVN_REPOSITORY_PATH . '/' . $repository_name);

        //判断是否创建成功
        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '添加仓库失败';
            return $data;
        }

        FunRequestReplyExec('setenforce 0');

        //写入配置文件
        $status = FunSetRepAuthz($this->globalAuthzContent, $repository_name, '/');
        if ($status != '1') {
            FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_AUTHZ);
        }

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    //项目管理 按钮 删除svn仓库
    function DeleteRepository($requestPayload)
    {
        $repository_name = $requestPayload['repository_name'];

        if (empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        //删除仓库目录
        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '仓库不存在';
            return $data;
        }
        FunRequestReplyExec('rm -rf ' . SVN_REPOSITORY_PATH . '/' . $repository_name);

        //检查是否删除成功
        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '删除仓库失败';
            return $data;
        }

        //从配置文件删除
        $status = FunDelRepAuthz($this->globalAuthzContent, $repository_name);
        if ($status != '1') {
            FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_AUTHZ);
        }

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 为仓库设置用户的权限
     */
    function SetRepositoryUserPrivilege($requestPayload)
    {
        $repository_name = trim($requestPayload['repository_name']);
        $this_account_list = $requestPayload['this_account_list'];

        if (empty($repository_name) || empty($this_account_list)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '项目不存在';
            return $data;
        }

        $authzContent = $this->globalAuthzContent;
        foreach ($this_account_list as $key => $value) {
            $authzContent = FunSetRepUserPri($authzContent, $value['account'], $value['privilege'] == 'no' ? '' : $value['privilege'], $repository_name, '/');
            if ($authzContent == '0') {
                $data['status'] = 0;
                $data['message'] = '仓库字段在配置文件中不存在 请刷新修复';
                return $data;
            }
        }

        FunRequestReplyExec('echo \'' . $authzContent . '\' > ' . SVN_SERVER_AUTHZ);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 为仓库设置用户组的权限
     */
    function SetRepositoryGroupPrivilege($requestPayload)
    {
        $repository_name = trim($requestPayload['repository_name']);
        $this_account_list = $requestPayload['this_account_list'];

        if (empty($repository_name) || empty($this_account_list)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '项目不存在';
            return $data;
        }

        $authzContent = $this->globalAuthzContent;
        foreach ($this_account_list as $key => $value) {
            if ($authzContent == '0') {
                $data['status'] = 0;
                $data['message'] = '仓库字段在配置文件中不存在 请刷新修复';
                return $data;
            }
            $authzContent = FunSetRepGroupPri($authzContent, $value['account'], $value['privilege'] == 'no' ? '' : $value['privilege'], $repository_name, '/');
        }

        FunRequestReplyExec('echo \'' . $authzContent . '\' > ' . SVN_SERVER_AUTHZ);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    //项目管理 按钮 编辑svn项目 提交用户对svn项目的标题的修改
    function SetRepositoryInfo($requestPayload)
    {
        $old_repository_name = trim($requestPayload['old_repository_name']);
        $new_repository_name = trim($requestPayload['new_repository_name']);

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        //只能包含字母、数字、破折号、下划线或点字符，不能以句点开头或结尾
        if (!FunIsValidRepName($new_repository_name)) {
            $data['status'] = 0;
            $data['message'] = 'SVN仓库名称只能包含字母、数字、破折号、下划线、点，不能以点开头或结尾';
            return $data;
        }

        //目录是否存在判断
        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $old_repository_name)) {
            $data['status'] = 0;
            $data['message'] = '要修改的项目不存在';
            return $data;
        }

        //是否重复
        if (is_dir(SVN_REPOSITORY_PATH . '/' . $new_repository_name)) {
            $data['status'] = 0;
            $data['message'] = '项目名称冲突';
            return $data;
        }

        //修改仓库文件夹的目录
        FunRequestReplyExec('mv ' . SVN_REPOSITORY_PATH . '/' . $old_repository_name . ' ' . SVN_REPOSITORY_PATH . '/' . $new_repository_name);

        //修改配置文件
        $status = FunUpdRepAuthz($this->globalAuthzContent, $old_repository_name, $new_repository_name);
        if ($status != '1') {
            FunRequestReplyExec('echo \'' . $status . '\' > ' . SVN_SERVER_AUTHZ);
        }

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * 获取某个仓库的带有权限的用户列表
     */
    function GetRepositoryUserPrivilegeList($requestPayload)
    {
        $repository_name = trim($requestPayload['repository_name']);

        if (empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '项目不存在';
            return $data;
        }

        $status1 = FunGetRepUserListWithPri($this->globalAuthzContent, $repository_name, '/');
        if ($status1 == '0') {
            $data['status'] = 0;
            $data['message'] = '仓库字段在配置文件中不存在 请刷新修复';
            return $data;
        }

        $status2 = FunGetSvnUserList($this->globalPasswdContent);
        if ($status2 == '0') {
            $data['status'] = 0;
            $data['message'] = '文件中不存在[users]标识';
            return $data;
        }

        $userNamePri = array_column($status1, 'userPri', 'userName');

        $userList = array_column($status2, 'userName');

        $list = [];
        foreach ($userList as $value) {
            if (array_key_exists($value, $userNamePri)) {
                array_push($list, array(
                    'account' => $value,
                    'privilege' => $userNamePri[$value] == '' ? 'no' : $userNamePri[$value]
                ));
            } else {
                array_push($list, array(
                    'account' => $value,
                    'privilege' => 'no'
                ));
            }
        }

        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = $list;
        return $data;
    }

    /**
     * 获取仓库目录树
     */
    public function GetRepTree($requestPayload)
    {
        $repository_name = trim($requestPayload['repository_name']);

        if (empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '仓库不存在';
            return $data;
        }

        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = FunGetRepTree($repository_name);
        return $data;
    }

    /**
     * 获取某个仓库的带有权限的用户组列表
     */
    function GetRepositoryGroupPrivilegeList($requestPayload)
    {
        $repository_name = trim($requestPayload['repository_name']);

        if (empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        if (!is_dir(SVN_REPOSITORY_PATH . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '项目不存在';
            return $data;
        }

        $status1 = FunGetRepGroupListWithPri($this->globalAuthzContent, $repository_name, '/');
        if ($status1 == '0') {
            $data['status'] = 0;
            $data['message'] = '仓库字段在配置文件中不存在 请刷新修复';
            return $data;
        } else {
            $status2 = FunGetSvnGroupList($this->globalAuthzContent);
            if ($status2 == '0') {
                $data['status'] = 0;
                $data['message'] = '文件中不存在[groups]标识';
                return $data;
            } else {
                $groupList = array_column($status1, 'groupPri', 'groupName');
                $list = [];
                foreach ($status2 as $value) {
                    if (array_key_exists($value, $groupList)) {
                        array_push($list, array(
                            'account' => $value,
                            'privilege' => $groupList[$value]
                        ));
                    } else {
                        array_push($list, array(
                            'account' => $value,
                            'privilege' => 'no'
                        ));
                    }
                }
                $data['status'] = 1;
                $data['message'] = '成功';
                $data['data'] = $list;
                return $data;
            }
        }
    }

    //高级设置 初始化加载 列出svnserve服务的状态
    function GetSvnserveStatus($requestPayload)
    {
        //是否安装服务
        $info = FunRequestReplyExec('ps auxf|grep -v "grep"|grep svnserve');
        if ($info == ISNULL && !file_exists('/usr/bin/svnserve')) {
            $info = [];
            $info['status'] = '未安装'; //未安装
            $info['port'] = '3690';
            $info['type'] = 'error';

            $data['status'] = 1;
            $data['message'] = '成功';
            $data['data'] = $info;
            return $data;
        }
        //是否存在repository目录
        if (!is_dir(SVN_REPOSITORY_PATH)) {
            $info = [];
            $info['status'] = '异常'; //存储库目录不存在
            $info['port'] = '3690';
            $info['type'] = 'error';

            $data['status'] = 1;
            $data['message'] = '成功';
            $data['data'] = $info;
            return $data;
        }
        //是否启动
        $info = FunRequestReplyExec('ps auxf|grep -v "grep"|grep svnserve');
        if ($info == ISNULL && file_exists('/usr/bin/svnserve')) {
            $info = [];
            $info['status'] = '已停止'; //svn服务未启动
            $info['port'] = '3690';
            $info['type'] = 'warning';

            $data['status'] = 1;
            $data['message'] = '成功';
            $data['data'] = $info;
            return $data;
        }

        $info = [];
        $info['status'] = '运行中'; //svn服务未启动
        $info['port'] = '3690';
        $info['type'] = 'success';

        $data['status'] = 1;
        $data['message'] = '成功';
        $data['data'] = $info;
        return $data;
    }

    //高级设置 管理svnserve服务的状态
    function SetSvnserveStatus($requestPayload)
    {
        $action = $requestPayload['action'];

        if (empty($action)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($this->globalUserRoleId != 1) {
            $data['status'] = 0;
            $data['message'] = '非法用户';
            $data['code'] = 401;
            return $data;
        }

        switch ($action) {
            case 'startSvn':
                FunRequestReplyExec('systemctl start svnserve');
                break;
            case 'restartSvn':
                FunRequestReplyExec('systemctl restart svnserve');
                break;
            case 'stopSvn':
                FunRequestReplyExec('systemctl stop svnserve');
                break;
        }

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    //获取服务状态 检查相关的目录文件是否存在
    private function CheckSvnserveStatus()
    {
        //是否安装服务
        $info = FunRequestReplyExec('ps auxf|grep -v "grep"|grep svnserve');
        if ($info == ISNULL && !file_exists('/usr/bin/svnserve')) {
            $data['status'] = 0;
            $data['code'] = '00';
            $data['message'] = 'svn服务未安装';
            return $data;
        }
        //是否存在repository目录
        if (!is_dir(SVN_REPOSITORY_PATH)) {
            $data['status'] = 0;
            $data['code'] = '00';
            $data['message'] = '存储库目录不存在';
            return $data;
        }
        //是否启动
        $info = FunRequestReplyExec('ps auxf|grep -v "grep"|grep svnserve');
        if ($info == ISNULL && file_exists('/usr/bin/svnserve')) {
            $data['status'] = 0;
            $data['code'] = '01';
            $data['message'] = 'svn服务未启动';
            return $data;
        }
        $data['status'] = 1;
        $data['code'] = '11';
        return $data;
    }
}
