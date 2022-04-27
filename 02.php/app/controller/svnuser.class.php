<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-27 16:41:35
 * @Description: QQ:1801168257
 */

use SVNAdmin\svnUser\svnUser as SvnUserSvnUser;

class svnuser extends controller
{
    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
    }

    /**
     * 将SVN用户数据同步到数据库
     * 
     * 目的为维护用户启用状态和自定义备注信息
     */
    function SyncUserToDb()
    {
        $svnUserPassList =  \SVNAdmin\SVN\User::GetSvnUserPassList($this->globalPasswdContent);
        if ($svnUserPassList == 0) {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        $dbUserPassList = $this->database->select('svn_users', [
            'svn_user_id',
            'svn_user_name',
            'svn_user_pass',
            'svn_user_status',
            'svn_user_note'
        ]);

        $combinArray1 = array_combine(FunArrayColumn($svnUserPassList, 'userName'), FunArrayColumn($svnUserPassList, 'disabled'));
        $combinArray2 = array_combine(FunArrayColumn($svnUserPassList, 'userName'), FunArrayColumn($svnUserPassList, 'userPass'));
        foreach ($dbUserPassList as $value) {
            if (!in_array($value['svn_user_name'], FunArrayColumn($svnUserPassList, 'userName'))) {
                $this->database->delete('svn_users', [
                    'svn_user_name' => $value['svn_user_name']
                ]);
            } else {
                //更新启用状态和密码
                $this->database->update('svn_users', [
                    'svn_user_pass' => $combinArray2[$value['svn_user_name']],
                    'svn_user_status' => !$combinArray1[$value['svn_user_name']]
                ], [
                    'svn_user_name' => $value['svn_user_name']
                ]);
            }
        }

        foreach ($svnUserPassList as $value) {
            if (!in_array($value['userName'], FunArrayColumn($dbUserPassList, 'svn_user_name'))) {
                $this->database->insert('svn_users', [
                    'svn_user_name' => $value['userName'],
                    'svn_user_pass' => $value['userPass'],
                    'svn_user_status' => !$value['disabled'],
                    'svn_user_note' => ''
                ]);
            }
        }
    }

    /**
     * 获取全部的SVN用户
     * 
     * 只包含用户名和启用状态
     */
    function GetAllUserList()
    {
        $svnUserList = \SVNAdmin\SVN\User::GetSvnUserList($this->globalPasswdContent);
        if ($svnUserList == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        } else {
            FunMessageExit(200, 1, '成功', $svnUserList);
        }
    }

    /**
     * 获取带有分页的SVN用户
     * 
     * 只包含用户名和启用状态
     */
    function GetUserList()
    {
        //将SVN用户数据同步到数据库
        $this->SyncUserToDb();

        $pageSize = $this->requestPayload['pageSize'];
        $currentPage = $this->requestPayload['currentPage'];
        $searchKeyword = trim($this->requestPayload['searchKeyword']);

        //分页
        $begin = $pageSize * ($currentPage - 1);

        $list = $this->database->select('svn_users', [
            'svn_user_id',
            'svn_user_name',
            'svn_user_pass',
            'svn_user_status',
            'svn_user_note'
        ], [
            'AND' => [
                'OR' => [
                    'svn_user_name[~]' => $searchKeyword,
                    // 'svn_user_pass[~]' => $searchKeyword,
                    'svn_user_note[~]' => $searchKeyword,
                ],
            ],
            'LIMIT' => [$begin, $pageSize],
            'ORDER' => [
                $this->requestPayload['sortName']  => strtoupper($this->requestPayload['sortType'])
            ]
        ]);

        $total = $this->database->count('svn_users', [
            'svn_user_id'
        ], [
            'AND' => [
                'OR' => [
                    'svn_user_name[~]' => $searchKeyword,
                    // 'svn_user_pass[~]' => $searchKeyword,
                    'svn_user_note[~]' => $searchKeyword,
                ],
            ],
        ]);

        foreach ($list as $key => $value) {
            $list[$key]['svn_user_status'] = $value['svn_user_status'] == '1' ? true : false;
        }

        FunMessageExit(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 启用SVN用户
     */
    function EnableUser()
    {
        $result = \SVNAdmin\SVN\User::EnabledUser($this->globalPasswdContent, $this->requestPayload['svn_user_name']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '要启用的用户不存在');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_PASSWD_FILE);

        FunMessageExit();
    }

    /**
     * 禁用SVN用户
     */
    function DisableUser()
    {
        $result = \SVNAdmin\SVN\User::DisabledUser($this->globalPasswdContent, $this->requestPayload['svn_user_name']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '要禁用的用户不存在');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_PASSWD_FILE);

        FunMessageExit();
    }

    /**
     * 修改SVN用户的备注信息
     */
    function EditUserNote()
    {
        $this->database->update('svn_users', [
            'svn_user_note' => $this->requestPayload['svn_user_note']
        ], [
            'svn_user_name' => $this->requestPayload['svn_user_name']
        ]);

        FunMessageExit();
    }

    /**
     * 新建SVN用户
     */
    function CreateUser()
    {
        //检查用户名是否合法
        FunCheckRepUser($this->requestPayload['svn_user_name']);

        //检查用户是否已存在
        $result = \SVNAdmin\SVN\User::AddSvnUser($this->globalPasswdContent, $this->requestPayload['svn_user_name'], $this->requestPayload['svn_user_pass']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '用户已存在');
        }

        //检查密码是否不为空
        if (trim($this->requestPayload['svn_user_pass']) == '') {
            FunMessageExit(200, 0, '密码不能为空');
        }

        //写入配置文件
        FunShellExec('echo \'' . $result . '\' > ' . SVN_PASSWD_FILE);

        //写入数据库
        $this->database->insert('svn_users', [
            'svn_user_name' => $this->requestPayload['svn_user_name'],
            'svn_user_pass' => $this->requestPayload['svn_user_pass'],
            'svn_user_status' => 1,
            'svn_user_note' => ''
        ]);

        FunMessageExit();
    }

    /**
     * 修改SVN用户的密码
     */
    function EditUserPass()
    {
        //检查用户是否已存在
        $result = \SVNAdmin\SVN\User::UpdSvnUserPass($this->globalPasswdContent, $this->requestPayload['svn_user_name'], $this->requestPayload['svn_user_pass'], !$this->requestPayload['svn_user_status']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '用户不存在 请刷新重试');
        }

        //检查密码是否不为空
        if (trim($this->requestPayload['svn_user_pass']) == '') {
            FunMessageExit(200, 0, '密码不能为空');
        }

        //写入配置文件
        FunShellExec('echo \'' . $result . '\' > ' . SVN_PASSWD_FILE);

        //写入数据库
        $this->database->update('svn_users', [
            'svn_user_pass' => $this->requestPayload['svn_user_pass'],
        ], [
            'svn_user_name' => $this->requestPayload['svn_user_name']
        ]);

        FunMessageExit();
    }

    /**
     * 删除SVN用户
     */
    function DelUser()
    {
        //从passwd文件中全局删除
        $resultPasswd = \SVNAdmin\SVN\User::DelSvnUserPasswd($this->globalPasswdContent, $this->requestPayload['svn_user_name'], !$this->requestPayload['svn_user_status']);

        if ($resultPasswd == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($resultPasswd == '1') {
            FunMessageExit(200, 0, '用户不存在');
        }

        //从authz文件中删除
        $resultAuthz = \SVNAdmin\SVN\User::DelUserAuthz($this->globalAuthzContent, $this->requestPayload['svn_user_name']);

        if ($resultAuthz == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }

        //从数据删除
        $this->database->delete('svn_users', [
            'svn_user_name' => $this->requestPayload['svn_user_name']
        ]);

        FunShellExec('echo \'' . $resultAuthz . '\' > ' . SVN_AUTHZ_FILE);

        FunShellExec('echo \'' . $resultPasswd . '\' > ' . SVN_PASSWD_FILE);

        FunMessageExit();
    }
}
