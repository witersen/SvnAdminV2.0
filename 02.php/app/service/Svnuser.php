<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use app\service\Logs as ServiceLogs;
use app\service\Ldap as ServiceLdap;
use app\service\Usersource as ServiceUsersource;

class Svnuser extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $ServiceLogs;
    private $ServiceLdap;
    private $ServiceUsersource;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->ServiceLogs = new ServiceLogs();
        $this->ServiceLdap = new ServiceLdap();
        $this->ServiceUsersource = new ServiceUsersource();
    }

    /**
     * SVN用户 => 数据库
     */
    public function SyncUserToDb()
    {
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];

        if ($dataSource['user_source'] == 'ldap') {
            $ldapUsers = $this->ServiceLdap->GetLdapUsers();
            if ($ldapUsers['status'] != 1) {
                return message($ldapUsers['code'], $ldapUsers['status'], $ldapUsers['message'], $ldapUsers['data']);
            }

            $ldapUsers = $ldapUsers['data'];

            //过滤空白用户
            $ldapUsers = array_values(array_filter($ldapUsers, 'funArrayValueFilter'));

            /**
             * 删除数据表重复插入的项
             */
            $dbUserList = $this->database->select('svn_users', [
                'svn_user_id',
                'svn_user_name',
                'svn_user_pass',
                'svn_user_status',
                'svn_user_note'
            ], [
                'GROUP' => [
                    'svn_user_name'
                ]
            ]);
            $dbUserListAll = $this->database->select('svn_users', [
                'svn_user_id',
                'svn_user_name',
            ]);

            $duplicates = array_diff(array_column($dbUserListAll, 'svn_user_id'), array_column($dbUserList, 'svn_user_id'));
            foreach ($duplicates as $value) {
                $this->database->delete('svn_users', [
                    'svn_user_id' => $value,
                ]);
            }

            /**
             * 数据对比增删改
             */
            $old = array_column($dbUserList, 'svn_user_name');
            $oldCombin = array_combine($old, $dbUserList);
            $new = $ldapUsers;

            //删除
            $delete = array_diff($old, $new);
            foreach ($delete as $value) {
                $this->database->delete('svn_users', [
                    'svn_user_name' => $value,
                ]);
            }

            //新增
            $create = array_diff($new, $old);
            foreach ($create as $value) {
                $this->database->insert('svn_users', [
                    'svn_user_name' => $value,
                    'svn_user_pass' => '',
                    'svn_user_status' => 1,
                    'svn_user_note' => ''
                ]);
            }
        } else {
            /**
             * 删除数据表重复插入的项
             */
            $dbUserList = $this->database->select('svn_users', [
                'svn_user_id',
                'svn_user_name',
                'svn_user_pass',
                'svn_user_status',
                'svn_user_note'
            ], [
                'GROUP' => [
                    'svn_user_name'
                ]
            ]);
            $dbUserListAll = $this->database->select('svn_users', [
                'svn_user_id',
                'svn_user_name',
            ]);

            $duplicates = array_diff(array_column($dbUserListAll, 'svn_user_id'), array_column($dbUserList, 'svn_user_id'));
            foreach ($duplicates as $value) {
                $this->database->delete('svn_users', [
                    'svn_user_id' => $value,
                ]);
            }

            /**
             * 数据对比增删改
             */
            $old = array_column($dbUserList, 'svn_user_name');
            $oldCombin = array_combine($old, $dbUserList);
            $svnUserList =  $this->SVNAdmin->GetUserInfo($this->passwdContent);
            if (is_numeric($svnUserList)) {
                if ($svnUserList == 621) {
                    return message(200, 0, '文件格式错误(不存在[users]标识)');
                } else if ($svnUserList == 710) {
                    return message(200, 0, '用户不存在');
                } else {
                    return message(200, 0, "错误码$svnUserList");
                }
            }
            $new = array_column($svnUserList, 'userName');
            $newCombin = array_combine($new, $svnUserList);

            //删除
            $delete = array_diff($old, $new);
            foreach ($delete as $value) {
                $this->database->delete('svn_users', [
                    'svn_user_name' => $value,
                ]);
            }

            //新增
            $create = array_diff($new, $old);
            foreach ($create as $value) {
                $this->database->insert('svn_users', [
                    'svn_user_name' => $value,
                    'svn_user_pass' => $newCombin[$value]['userPass'],
                    'svn_user_status' => $newCombin[$value]['disabled'] == 1 ? 0 : 1,
                    'svn_user_note' => ''
                ]);
            }

            //更新
            $update = array_intersect($old, $new);
            foreach ($update as $value) {
                if (
                    $oldCombin[$value]['svn_user_pass'] != $newCombin[$value]['userPass'] ||
                    $oldCombin[$value]['svn_user_status'] != ($newCombin[$value]['disabled'] == 1 ? 0 : 1)
                ) {
                    $this->database->update('svn_users', [
                        'svn_user_pass' => $newCombin[$value]['userPass'],
                        'svn_user_status' => $newCombin[$value]['disabled'] == 1 ? 0 : 1,
                    ], [
                        'svn_user_name' => $value
                    ]);
                }
            }
        }

        return message();
    }

    /**
     * 获取带有分页的SVN用户
     * 
     * 只包含用户名和启用状态
     * 
     * 管理员
     * SVN用户
     */
    public function GetUserList()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'sync' => ['type' => 'boolean'],
            'page' => ['type' => 'boolean'],
            'pageSize' => ['type' => 'integer', 'required' => isset($this->payload['page']) && $this->payload['page'] ? true : false],
            'currentPage' => ['type' => 'integer', 'required' => isset($this->payload['page']) && $this->payload['page'] ? true : false],
            'searchKeyword' => ['type' => 'string', 'notNull' => false],
            'sortName' => ['type' => 'string', 'notNull' => true],
            'sortType' => ['type' => 'string', 'notNull' => true],
            'svnn_user_pri_path_id' => ['type' => 'integer', 'required' => $this->userRoleId == 2]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        //检查排序字段
        if (!in_array($this->payload['sortName'], ['svn_user_id', 'svn_user_name', 'svn_user_status', 'svn_user_last_login'])) {
            return message(2000, '不允许的排序字段');
        }
        if (!in_array($this->payload['sortType'], ['asc', 'desc', 'ASC', 'DESC'])) {
            return message(2000, '不允许的排序类型');
        }

        $sync = $this->payload['sync'];
        $page = $this->payload['page'];
        $searchKeyword = trim($this->payload['searchKeyword']);

        //将SVN用户数据同步到数据库
        if ($sync) {
            $syncResult = $this->SyncUserToDb();
            if ($syncResult['status'] != 1) {
                return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
            }
        }

        if ($page) {
            $pageSize = $this->payload['pageSize'];
            $currentPage = $this->payload['currentPage'];
            $begin = $pageSize * ($currentPage - 1);

            $result = $this->database->select('svn_users', [
                'svn_user_id',
                'svn_user_name',
                'svn_user_pass',
                'svn_user_status [Int]',
                'svn_user_note',
                'svn_user_last_login',
                'svn_user_token'
            ], [
                'AND' => [
                    'OR' => [
                        'svn_user_name[~]' => $searchKeyword,
                        'svn_user_note[~]' => $searchKeyword,
                    ],
                ],
                'LIMIT' => [$begin, $pageSize],
                'ORDER' => [
                    $this->payload['sortName']  => strtoupper($this->payload['sortType'])
                ]
            ]);
        } else {
            $result = $this->database->select('svn_users', [
                'svn_user_id',
                'svn_user_name',
                'svn_user_pass',
                'svn_user_status [Int]',
                'svn_user_note',
                'svn_user_last_login',
                'svn_user_token'
            ], [
                'AND' => [
                    'OR' => [
                        'svn_user_name[~]' => $searchKeyword,
                        'svn_user_note[~]' => $searchKeyword,
                    ],
                ],
                'ORDER' => [
                    $this->payload['sortName']  => strtoupper($this->payload['sortType'])
                ]
            ]);
        }

        $total = $this->database->count('svn_users', [
            'svn_user_id'
        ], [
            'AND' => [
                'OR' => [
                    'svn_user_name[~]' => $searchKeyword,
                    'svn_user_note[~]' => $searchKeyword,
                ],
            ],
        ]);

        //针对SVN用户可管理对象进行过滤
        if ($this->userRoleId == 2) {
            $filters = $this->database->select('svn_second_pri', [
                '[>]svn_user_pri_paths' => ['svnn_user_pri_path_id' => 'svnn_user_pri_path_id']
            ], [
                'svn_second_pri.svn_object_type(objectType)',
                'svn_second_pri.svn_object_name(objectName)',
            ], [
                'svn_user_pri_paths.svn_user_name' => $this->userName,
                'svn_user_pri_paths.svnn_user_pri_path_id' => $this->payload['svnn_user_pri_path_id']
            ]);
            foreach ($result as $key => $value) {
                if (!in_array([
                    'objectType' => 'user',
                    'objectName' => $value['svn_user_name']
                ], $filters)) {
                    unset($result[$key]);
                }
            }
        }

        $time = time();
        foreach ($result as $key => $value) {
            $result[$key]['svn_user_status'] = $value['svn_user_status'] == 1 ? true : false;
            $result[$key]['online'] = (empty($value['svn_user_token']) || $value['svn_user_token'] == '-') ? false : (explode($this->configSign['signSeparator'], $value['svn_user_token'])[3] > $time);
            unset($result[$key]['svn_user_token']);
        }

        return message(200, 1, '成功', [
            'data' => $result,
            'total' => $total
        ]);
    }

    /**
     * 自动识别 passwd 文件中的用户列表并返回
     */
    public function ScanPasswd()
    {
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap') {
            return message(200, 0, '当前SVN用户来源为LDAP-不支持此操作');
        }

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
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap') {
            return message(200, 0, '当前SVN用户来源为LDAP-不支持此操作');
        }

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

        funFilePutContents($this->configSvn['svn_passwd_file'], $result);

        $this->database->update('svn_users', [
            'svn_user_status' => $this->payload['status'] ? 1 : 0,
        ], [
            'svn_user_name' => $this->payload['svn_user_name']
        ]);

        return message();
    }

    /**
     * 修改SVN用户的备注信息
     */
    public function UpdUserNote()
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
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap') {
            return message(200, 0, '当前SVN用户来源为LDAP-不支持此操作');
        }

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
        funFilePutContents($this->configSvn['svn_passwd_file'], $result);

        //写入数据库
        $this->database->delete('svn_users', [
            'svn_user_name' => $this->payload['svn_user_name'],
        ]);
        $this->database->insert('svn_users', [
            'svn_user_name' => $this->payload['svn_user_name'],
            'svn_user_pass' => $this->payload['svn_user_pass'],
            'svn_user_status' => 1,
            'svn_user_note' => $this->payload['svn_user_note']
        ]);

        //日志
        $this->ServiceLogs->InsertLog(
            '创建用户',
            sprintf("用户名:%s", $this->payload['svn_user_name']),
            $this->userName
        );

        return message();
    }

    /**
     * 修改SVN用户的密码
     */
    public function UpdUserPass()
    {
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap') {
            return message(200, 0, '当前SVN用户来源为LDAP-不支持此操作');
        }

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
        funFilePutContents($this->configSvn['svn_passwd_file'], $result);

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
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap') {
            return message(200, 0, '当前SVN用户来源为LDAP-不支持此操作');
        }

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

        funFilePutContents($this->configSvn['svn_authz_file'], $resultAuthz);

        funFilePutContents($this->configSvn['svn_passwd_file'], $resultPasswd);

        //日志
        $this->ServiceLogs->InsertLog(
            '删除用户',
            sprintf("用户名:%s", $this->payload['svn_user_name']),
            $this->userName
        );

        return message();
    }
}
