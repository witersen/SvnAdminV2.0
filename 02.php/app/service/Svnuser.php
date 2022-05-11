<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-11 02:21:30
 * @Description: QQ:1801168257
 */

namespace app\service;

class Svnuser extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $Logs;

    function __construct()
    {
        parent::__construct();

        $this->Logs = new Logs();
    }

    /**
     * 将SVN用户数据同步到数据库
     * 
     * 目的为维护用户启用状态和自定义备注信息
     */
    public function SyncUserToDb()
    {
        $svnUserPassList =  $this->SVNAdminUser->GetSvnUserPassList($this->passwdContent);
        if ($svnUserPassList == 0) {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
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

        return message();
    }

    /**
     * 获取全部的SVN用户
     * 
     * 只包含用户名和启用状态
     */
    public function GetAllUserList()
    {
        $svnUserList = $this->SVNAdminUser->GetSvnUserList($this->passwdContent);
        if ($svnUserList == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        } else {
            return message(200, 1, '成功', $svnUserList);
        }
    }

    /**
     * 获取带有分页的SVN用户
     * 
     * 只包含用户名和启用状态
     */
    public function GetUserList()
    {
        //将SVN用户数据同步到数据库
        $syncResult = $this->SyncUserToDb();
        if ($syncResult['status'] != 1) {
            return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
        }

        $pageSize = $this->payload['pageSize'];
        $currentPage = $this->payload['currentPage'];
        $searchKeyword = trim($this->payload['searchKeyword']);

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
                $this->payload['sortName']  => strtoupper($this->payload['sortType'])
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

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 启用SVN用户
     */
    public function EnableUser()
    {
        $result = $this->SVNAdminUser->EnabledUser($this->passwdContent, $this->payload['svn_user_name']);
        if ($result == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            return message(200, 0, '要启用的用户不存在');
        }

        // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_passwd_file']);

        FunFilePutContents($this->config_svn['svn_passwd_file'], $result);

        return message();
    }

    /**
     * 禁用SVN用户
     */
    public function DisableUser()
    {
        $result = $this->SVNAdminUser->DisabledUser($this->passwdContent, $this->payload['svn_user_name']);
        if ($result == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            return message(200, 0, '要禁用的用户不存在');
        }

        // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_passwd_file']);

        FunFilePutContents($this->config_svn['svn_passwd_file'], $result);

        return message();
    }

    /**
     * 修改SVN用户的备注信息
     */
    public function EditUserNote()
    {
        $this->database->update('svn_users', [
            'svn_user_note' => $this->payload['svn_user_note']
        ], [
            'svn_user_name' => $this->payload['svn_user_name']
        ]);

        return message(200, 1, '已保存');
    }

    /**
     * 新建SVN用户
     */
    public function CreateUser()
    {
        //检查用户名是否合法
        $checkResult = $this->checkService->CheckRepUser($this->payload['svn_user_name']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //检查用户是否已存在
        $result = $this->SVNAdminUser->AddSvnUser($this->passwdContent, $this->payload['svn_user_name'], $this->payload['svn_user_pass']);
        if ($result == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            return message(200, 0, '用户已存在');
        }

        //检查密码是否不为空
        if (trim($this->payload['svn_user_pass']) == '') {
            return message(200, 0, '密码不能为空');
        }

        //写入配置文件
        // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_passwd_file']);

        FunFilePutContents($this->config_svn['svn_passwd_file'], $result);

        //写入数据库
        $this->database->insert('svn_users', [
            'svn_user_name' => $this->payload['svn_user_name'],
            'svn_user_pass' => $this->payload['svn_user_pass'],
            'svn_user_status' => 1,
            'svn_user_note' => ''
        ]);

        //日志
        $this->Logs->InsertLog(
            '创建用户',
            sprintf("用户名 %s", $this->payload['svn_user_name']),
            $this->userName
        );

        return message();
    }

    /**
     * 修改SVN用户的密码
     */
    public function EditUserPass()
    {
        //检查用户是否已存在
        $result = $this->SVNAdminUser->UpdSvnUserPass($this->passwdContent, $this->payload['svn_user_name'], $this->payload['svn_user_pass'], !$this->payload['svn_user_status']);
        if ($result == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            return message(200, 0, '用户不存在 请刷新重试');
        }

        //检查密码是否不为空
        if (trim($this->payload['svn_user_pass']) == '') {
            return message(200, 0, '密码不能为空');
        }

        //写入配置文件
        // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_passwd_file']);

        FunFilePutContents($this->config_svn['svn_passwd_file'], $result);

        //写入数据库
        $this->database->update('svn_users', [
            'svn_user_pass' => $this->payload['svn_user_pass'],
        ], [
            'svn_user_name' => $this->payload['svn_user_name']
        ]);

        return message();
    }

    /**
     * 删除SVN用户
     */
    public function DelUser()
    {
        //从passwd文件中全局删除
        $resultPasswd = $this->SVNAdminUser->DelSvnUserPasswd($this->passwdContent, $this->payload['svn_user_name'], !$this->payload['svn_user_status']);

        if ($resultPasswd == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($resultPasswd == '1') {
            return message(200, 0, '用户不存在');
        }

        //从authz文件中删除
        $resultAuthz = $this->SVNAdminUser->DelUserAuthz($this->authzContent, $this->payload['svn_user_name']);

        if ($resultAuthz == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        }

        //从数据删除
        $this->database->delete('svn_users', [
            'svn_user_name' => $this->payload['svn_user_name']
        ]);

        // FunShellExec('echo \'' . $resultAuthz . '\' > ' . $this->config_svn['svn_authz_file']);

        FunFilePutContents($this->config_svn['svn_authz_file'], $resultAuthz);

        // FunShellExec('echo \'' . $resultPasswd . '\' > ' . $this->config_svn['svn_passwd_file']);

        FunFilePutContents($this->config_svn['svn_passwd_file'], $resultPasswd);

        //日志
        $this->Logs->InsertLog(
            '删除用户',
            sprintf("用户名 %s", $this->payload['svn_user_name']),
            $this->userName
        );

        return message();
    }
}
