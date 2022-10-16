<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 11:39:36
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
        $svnUserPassList =  $this->SVNAdmin->GetUserInfo($this->passwdContent);
        if (is_numeric($svnUserPassList)) {
            if ($svnUserPassList == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($svnUserPassList == 710) {
                return message(200, 0, '用户不存在');
            } else {
                return message(200, 0, "错误码$svnUserPassList");
            }
        }

        $dbUserPassList = $this->database->select('svn_users', [
            'svn_user_id',
            'svn_user_name',
            'svn_user_pass',
            'svn_user_status',
            'svn_user_note'
        ]);

        $combinArray1 = array_combine(array_column($svnUserPassList, 'userName'), array_column($svnUserPassList, 'disabled'));
        $combinArray2 = array_combine(array_column($svnUserPassList, 'userName'), array_column($svnUserPassList, 'userPass'));
        foreach ($dbUserPassList as $value) {
            if (!in_array($value['svn_user_name'], array_column($svnUserPassList, 'userName'))) {
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
            if (!in_array($value['userName'], array_column($dbUserPassList, 'svn_user_name'))) {
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
     */
    public function GetAllUserList()
    {
        $searchKeyword = trim($this->payload['searchKeywordUser']);

        $list = $this->database->select('svn_users', [
            'svn_user_id',
            'svn_user_name',
            'svn_user_status',
            'svn_user_note'
        ], [
            'AND' => [
                'OR' => [
                    'svn_user_name[~]' => $searchKeyword,
                    'svn_user_note[~]' => $searchKeyword,
                ],
            ],
        ]);

        return message(200, 1, '成功', $list);
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
            'svn_user_status [Int]',
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
            $list[$key]['svn_user_status'] = $value['svn_user_status'] == 1 ? true : false;
        }

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 自动识别 passwd 文件中的用户列表并返回
     */
    public function ScanPasswd()
    {
        if ($this->payload['passwdContent'] == '') {
            return message(200, 0, '内容不能为空');
        }

        $svnUserPassList = $this->SVNAdmin->GetUserInfo($this->payload['passwdContent']);
        if (is_numeric($svnUserPassList)) {
            if ($svnUserPassList == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($svnUserPassList == 710) {
                return message(200, 0, '用户不存在');
            } else {
                return message(200, 0, "错误码$svnUserPassList");
            }
        }

        return message(200, 1, '成功', $svnUserPassList);
    }

    /**
     * 启用或禁用用户
     */
    public function UpdUserStatus()
    {
        //status true 启用用户 false 禁用用户
        $result = $this->SVNAdmin->UpdUserStatus($this->passwdContent, $this->payload['svn_user_name'], !$this->payload['status']);
        if (is_numeric($result)) {
            if ($result == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($result == 710) {
                return message(200, 0, '用户不存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        funFilePutContents($this->config_svn['svn_passwd_file'], $result);

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
        $result = $this->SVNAdmin->AddUser($this->passwdContent, $this->payload['svn_user_name'], $this->payload['svn_user_pass']);
        if (is_numeric($result)) {
            if ($result == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($result == 810) {
                return message(200, 0, '用户已存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        //检查密码是否不为空
        if (trim($this->payload['svn_user_pass']) == '') {
            return message(200, 0, '密码不能为空');
        }

        //写入配置文件
        funFilePutContents($this->config_svn['svn_passwd_file'], $result);

        //写入数据库
        $this->database->insert('svn_users', [
            'svn_user_name' => $this->payload['svn_user_name'],
            'svn_user_pass' => $this->payload['svn_user_pass'],
            'svn_user_status' => 1,
            'svn_user_note' => $this->payload['svn_user_note']
        ]);

        //日志
        $this->Logs->InsertLog(
            '创建用户',
            sprintf("用户名:%s", $this->payload['svn_user_name']),
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
        $result = $this->SVNAdmin->UpdUserPass($this->passwdContent, $this->payload['svn_user_name'], $this->payload['svn_user_pass'], !$this->payload['svn_user_status']);
        if (is_numeric($result)) {
            if ($result == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($result == 710) {
                return message(200, 0, '用户不存在 请刷新重试');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        //检查密码是否不为空
        if (trim($this->payload['svn_user_pass']) == '') {
            return message(200, 0, '密码不能为空');
        }

        //写入配置文件
        funFilePutContents($this->config_svn['svn_passwd_file'], $result);

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
        $resultPasswd = $this->SVNAdmin->DelUserFromPasswd($this->passwdContent, $this->payload['svn_user_name'], !$this->payload['svn_user_status']);
        if (is_numeric($resultPasswd)) {
            if ($resultPasswd == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($resultPasswd == 710) {
                return message(200, 0, '用户不存在');
            } else {
                return message(200, 0, "错误码$resultPasswd");
            }
        }

        //从authz文件中删除
        $resultAuthz = $this->SVNAdmin->DelObjectFromAuthz($this->authzContent, $this->payload['svn_user_name'], 'user');
        if (is_numeric($resultAuthz)) {
            if ($resultAuthz == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($resultAuthz == 901) {
                return message(200, 0, '不支持的授权对象类型');
            } else {
                return message(200, 0, "错误码$resultAuthz");
            }
        }

        //从数据删除
        $this->database->delete('svn_users', [
            'svn_user_name' => $this->payload['svn_user_name']
        ]);

        funFilePutContents($this->config_svn['svn_authz_file'], $resultAuthz);

        funFilePutContents($this->config_svn['svn_passwd_file'], $resultPasswd);

        //日志
        $this->Logs->InsertLog(
            '删除用户',
            sprintf("用户名:%s", $this->payload['svn_user_name']),
            $this->userName
        );

        return message();
    }
}
