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

class Svngroup extends Base
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
     * SVN分组 => 数据库
     */
    private function SyncGroupToDb()
    {
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap' && $dataSource['group_source'] == 'ldap') {
            $ldapGroups = $this->ServiceLdap->GetLdapGroups();
            if ($ldapGroups['status'] != 1) {
                return message($ldapGroups['code'], $ldapGroups['status'], $ldapGroups['message'], $ldapGroups['data']);
            }

            $ldapGroups = $ldapGroups['data'];

            //过滤空白分组
            $ldapGroups = array_values(array_filter($ldapGroups, 'funArrayValueFilter'));

            /**
             * 删除数据表重复插入的项
             */
            $dbGroupList = $this->database->select('svn_groups', [
                'svn_group_id',
                'svn_group_name',
                'svn_group_note',
                'include_user_count [Int]',
                'include_group_count [Int]',
                'include_aliase_count [Int]'
            ], [
                'GROUP' => [
                    'svn_group_name'
                ]
            ]);
            $dbGroupListAll = $this->database->select('svn_groups', [
                'svn_group_id',
                'svn_group_name',
            ]);

            $duplicates = array_diff(array_column($dbGroupListAll, 'svn_group_id'), array_column($dbGroupList, 'svn_group_id'));
            foreach ($duplicates as $value) {
                $this->database->delete('svn_groups', [
                    'svn_group_id' => $value,
                ]);
            }

            /**
             * 数据对比增删改
             */
            $old = array_column($dbGroupList, 'svn_group_name');
            $oldCombin = array_combine($old, $dbGroupList);
            $new = $ldapGroups;

            //删除
            $delete = array_diff($old, $new);
            foreach ($delete as $value) {
                $this->database->delete('svn_groups', [
                    'svn_group_name' => $value,
                ]);
            }

            //新增
            $create = array_diff($new, $old);
            foreach ($create as $value) {
                $this->database->insert('svn_groups', [
                    'svn_group_name' => $value,
                    'include_user_count' => 0,
                    'include_group_count' => 0,
                    'include_aliase_count' => 0,
                    'svn_group_note' => '',
                ]);
            }

            //清空旧authz分组
            $result = $this->SVNAdmin->ClearGroupSection($this->authzContent);
            if (is_numeric($result)) {
                if ($result == 612) {
                    return message(200, 0, '文件格式错误(不存在[groups]标识)');
                } else {
                    return message(200, 0, "错误码$result");
                }
            }
            file_put_contents($this->configSvn['svn_authz_file'], $result);
            $this->authzContent = $result;

            //写入新authz
            $authzContent = $this->authzContent;
            foreach ($ldapGroups as $group) {
                $result = $this->SVNAdmin->AddGroup($authzContent, $group);
                if (is_numeric($result)) {
                    if ($result == 612) {
                        return message(200, 0, '文件格式错误(不存在[groups]标识)');
                    } else if ($result == 820) {
                        //分组已存在
                        continue;
                    } else {
                        //其它错误码
                        continue;
                    }
                }
                $authzContent = $result;
            }

            file_put_contents($this->configSvn['svn_authz_file'], $authzContent);
            $this->authzContent = $authzContent;
        } else {
            /**
             * 删除数据表重复插入的项
             */
            $dbGroupList = $this->database->select('svn_groups', [
                'svn_group_id',
                'svn_group_name',
                'svn_group_note',
                'include_user_count [Int]',
                'include_group_count [Int]',
                'include_aliase_count [Int]'
            ], [
                'GROUP' => [
                    'svn_group_name'
                ]
            ]);
            $dbGroupListAll = $this->database->select('svn_groups', [
                'svn_group_id',
                'svn_group_name',
            ]);

            $duplicates = array_diff(array_column($dbGroupListAll, 'svn_group_id'), array_column($dbGroupList, 'svn_group_id'));
            foreach ($duplicates as $value) {
                $this->database->delete('svn_groups', [
                    'svn_group_id' => $value,
                ]);
            }

            /**
             * 数据对比增删改
             */
            $old = array_column($dbGroupList, 'svn_group_name');
            $oldCombin = array_combine($old, $dbGroupList);

            $svnGroupList = $this->SVNAdmin->GetGroupInfo($this->authzContent);
            if (is_numeric($svnGroupList)) {
                if ($svnGroupList == 612) {
                    return message(200, 0, '文件格式错误(不存在[groups]标识)');
                } else {
                    return message(200, 0, "错误码$svnGroupList");
                }
            }

            $new = array_column($svnGroupList, 'groupName');
            $newCombin = array_combine($new, $svnGroupList);

            //删除
            $delete = array_diff($old, $new);
            foreach ($delete as $value) {
                $this->database->delete('svn_groups', [
                    'svn_group_name' => $value,
                ]);
            }

            //新增
            $create = array_diff($new, $old);
            foreach ($create as $value) {
                $this->database->insert('svn_groups', [
                    'svn_group_name' => $value,
                    'include_user_count' => $newCombin[$value]['include']['users']['count'],
                    'include_group_count' => $newCombin[$value]['include']['groups']['count'],
                    'include_aliase_count' => $newCombin[$value]['include']['aliases']['count'],
                    'svn_group_note' => '',
                ]);
            }

            //更新
            $update = array_intersect($old, $new);
            foreach ($update as $value) {
                if (
                    $oldCombin[$value]['include_user_count'] !=  $newCombin[$value]['include']['users']['count'] ||
                    $oldCombin[$value]['include_group_count'] !=  $newCombin[$value]['include']['groups']['count'] ||
                    $oldCombin[$value]['include_aliase_count'] !=  $newCombin[$value]['include']['aliases']['count']
                ) {
                    $this->database->update('svn_groups', [
                        'include_user_count' => $newCombin[$value]['include']['users']['count'],
                        'include_group_count' => $newCombin[$value]['include']['groups']['count'],
                        'include_aliase_count' => $newCombin[$value]['include']['aliases']['count']
                    ], [
                        'svn_group_name' => $value
                    ]);
                }
            }
        }

        return message();
    }

    /**
     * 获取带有分页的分组列表
     */
    public function GetGroupList()
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
        if (!in_array($this->payload['sortName'], ['svn_group_id', 'svn_group_name'])) {
            return message(2000, '不允许的排序字段');
        }
        if (!in_array($this->payload['sortType'], ['asc', 'desc', 'ASC', 'DESC'])) {
            return message(2000, '不允许的排序类型');
        }

        $sync = $this->payload['sync'];
        $page = $this->payload['page'];
        $searchKeyword = trim($this->payload['searchKeyword']);

        if ($sync) {
            //同步
            $syncResult = $this->SyncGroupToDb();
            if ($syncResult['status'] != 1) {
                return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
            }
            //更新authz todo
        }

        if ($page) {
            $pageSize = $this->payload['pageSize'];
            $currentPage = $this->payload['currentPage'];
            $begin = $pageSize * ($currentPage - 1);

            $result = $this->database->select('svn_groups', [
                'svn_group_id',
                'svn_group_name',
                'svn_group_note',
                'include_user_count [Int]',
                'include_group_count [Int]',
                'include_aliase_count [Int]',
            ], [
                'AND' => [
                    'OR' => [
                        'svn_group_name[~]' => $searchKeyword,
                        'svn_group_note[~]' => $searchKeyword,
                    ],
                ],
                'LIMIT' => [$begin, $pageSize],
                'ORDER' => [
                    $this->payload['sortName']  => strtoupper($this->payload['sortType'])
                ]
            ]);
        } else {
            $result = $this->database->select('svn_groups', [
                'svn_group_id',
                'svn_group_name',
                'svn_group_note',
                'include_user_count [Int]',
                'include_group_count [Int]',
                'include_aliase_count [Int]',
            ], [
                'AND' => [
                    'OR' => [
                        'svn_group_name[~]' => $searchKeyword,
                        'svn_group_note[~]' => $searchKeyword,
                    ],
                ],
                'ORDER' => [
                    $this->payload['sortName']  => strtoupper($this->payload['sortType'])
                ]
            ]);
        }

        $total = $this->database->count('svn_groups',  [
            'svn_group_id'
        ], [
            'AND' => [
                'OR' => [
                    'svn_group_name[~]' => $searchKeyword,
                    'svn_group_note[~]' => $searchKeyword,
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
                    'objectType' => 'group',
                    'objectName' => $value['svn_group_name']
                ], $filters)) {
                    unset($result[$key]);
                }
            }
        }

        return message(200, 1, '成功', [
            'data' => $result,
            'total' => $total
        ]);
    }

    /**
     * 编辑分组备注信息
     */
    public function UpdGroupNote()
    {
        $this->database->update('svn_groups', [
            'svn_group_note' => $this->payload['svn_group_note']
        ], [
            'svn_group_name' => $this->payload['svn_group_name']
        ]);

        return message(200, 1, '已保存');
    }

    /**
     * 创建SVN分组
     */
    public function CreateGroup()
    {
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap' && $dataSource['group_source'] == 'ldap') {
            return message(200, 0, '当前SVN分组来源为LDAP-不支持此操作');
        }

        //检查分组名是否合法
        $checkResult = $this->checkService->CheckRepGroup($this->payload['svn_group_name']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //检查分组是否已存在
        $result = $this->SVNAdmin->AddGroup($this->authzContent, $this->payload['svn_group_name']);
        if (is_numeric($result)) {
            if ($result == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } else if ($result == 820) {
                return message(200, 0, '分组已存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        //写入配置文件
        funFilePutContents($this->configSvn['svn_authz_file'], $result);

        //写入数据库
        $this->database->delete('svn_groups', [
            'svn_group_name' => $this->payload['svn_group_name']
        ]);
        $this->database->insert('svn_groups', [
            'svn_group_name' => $this->payload['svn_group_name'],
            'include_user_count' => 0,
            'include_group_count' => 0,
            'include_aliase_count' => 0,
            'svn_group_note' => $this->payload['svn_group_note'],
        ]);

        //日志
        $this->ServiceLogs->InsertLog(
            '创建分组',
            sprintf("分组名:%s", $this->payload['svn_group_name']),
            $this->userName
        );

        return message();
    }

    /**
     * 删除SVN分组
     */
    public function DelGroup()
    {
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap' && $dataSource['group_source'] == 'ldap') {
            return message(200, 0, '当前SVN分组来源为LDAP-不支持此操作');
        }

        //从authz文件删除
        $result = $this->SVNAdmin->DelObjectFromAuthz($this->authzContent, $this->payload['svn_group_name'], 'group');
        if (is_numeric($result)) {
            if ($result == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } else if ($result == 901) {
                return message(200, 0, '不支持的授权对象类型');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        funFilePutContents($this->configSvn['svn_authz_file'], $result);

        //从数据库删除
        $this->database->delete('svn_groups', [
            'svn_group_name' => $this->payload['svn_group_name']
        ]);

        //日志
        $this->ServiceLogs->InsertLog(
            '删除分组',
            sprintf("分组名:%s", $this->payload['svn_group_name']),
            $this->userName
        );

        return message();
    }

    /**
     * 修改SVN分组的名称
     */
    public function UpdGroupName()
    {
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap' && $dataSource['group_source'] == 'ldap') {
            return message(200, 0, '当前SVN分组来源为LDAP-不支持此操作');
        }

        //新分组名称是否合法
        $checkResult = $this->checkService->CheckRepGroup($this->payload['groupNameNew']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //修改前同步下
        $syncResult = $this->SyncGroupToDb();
        if ($syncResult['status'] != 1) {
            return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
        }

        $result = $this->SVNAdmin->UpdObjectFromAuthz($this->authzContent, $this->payload['groupNameOld'], $this->payload['groupNameNew'], 'group');
        if (is_numeric($result)) {
            if ($result == 611) {
                return message(200, 0, 'authz文件格式错误(不存在[aliases]标识)');
            } else if ($result == 612) {
                return message(200, 0, 'authz文件格式错误(不存在[groups]标识)');
            } else if ($result == 901) {
                return message(200, 0, '不支持的授权对象类型');
            } else if ($result == 821) {
                return message(200, 0, '要修改的新分组已经存在');
            } else if ($result == 831) {
                return message(200, 0, '要修改的新别名已经存在');
            } else if ($result == 731) {
                return message(200, 0, '要修改的别名不存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        funFilePutContents($this->configSvn['svn_authz_file'], $result);

        //修改后同步下
        $this->authzContent = file_get_contents($this->configSvn['svn_authz_file']);
        $syncResult = $this->SyncGroupToDb();
        if ($syncResult['status'] != 1) {
            return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
        }

        return message();
    }

    /**
     * 获取SVN分组的成员列表
     */
    public function GetGroupMember()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'searchKeyword' => ['type' => 'string', 'notNull' => false],
            'svn_group_name' => ['type' => 'string', 'notNull' => true],
            'svnn_user_pri_path_id' => ['type' => 'integer', 'required' => $this->userRoleId == 2]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $searchKeyword = trim($this->payload['searchKeyword']);

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
            if (!in_array([
                'objectType' => 'group',
                'objectName' => $this->payload['svn_group_name']
            ], $filters)) {
                return message(200, 0, '无权限的操作对象');
            }
        }

        $list = $this->SVNAdmin->GetGroupInfo($this->authzContent, $this->payload['svn_group_name']);
        if (is_numeric($list)) {
            if ($list == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } else if ($list == 720) {
                return message(200, 0, '指定的分组不存在');
            } else {
                return message(200, 0, "错误码$list");
            }
        }

        $result = [];
        foreach ($list['include']['users']['list'] as $value) {
            if (empty($searchKeyword) || strstr($value, $searchKeyword)) {
                $result[] = [
                    'objectType' => 'user',
                    'objectName' => $value,
                ];
            }
        }
        foreach ($list['include']['groups']['list'] as $value) {
            if (empty($searchKeyword) || strstr($value, $searchKeyword)) {
                $result[] = [
                    'objectType' => 'group',
                    'objectName' => $value,
                ];
            }
        }
        foreach ($list['include']['aliases']['list'] as $value) {
            if (empty($searchKeyword) || strstr($value, $searchKeyword)) {
                $result[] = [
                    'objectType' => 'aliase',
                    'objectName' => $value,
                ];
            }
        }

        return message(200, 1, '成功', $result);
    }

    /**
     * 为分组添加或者删除所包含的对象
     * 对象包括：用户、分组、用户别名
     */
    public function UpdGroupMember()
    {
        $dataSource = $this->ServiceUsersource->GetUsersourceInfo()['data'];
        if ($dataSource['user_source'] == 'ldap' && $dataSource['group_source'] == 'ldap') {
            return message(200, 0, '当前SVN分组来源为LDAP-不支持此操作');
        }

        $result = $this->SVNAdmin->UpdGroupMember($this->authzContent, $this->payload['svn_group_name'], $this->payload['objectName'], $this->payload['objectType'], $this->payload['actionType']);
        if (is_numeric($result)) {
            if ($result == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } else if ($result == 720) {
                return message(200, 0, '分组不存在');
            } else if ($result == 803) {
                return message(200, 0, '要添加的对象已存在该分组');
            } else if ($result == 703) {
                return message(200, 0, '要删除的对象不存在该分组');
            } else if ($result == 901) {
                return message(200, 0, '无效的对象类型 user|group|aliase');
            } else if ($result == 902) {
                return message(200, 0, '无效的操作类型 add|delete');
            } else if ($result == 802) {
                return message(200, 0, '不能操作相同名称的分组');
            } else {
                return message(200, 0, "错误码$result");
            }
        }
        if ($this->payload['objectType'] == 'group' && $this->payload['actionType'] == 'add') {
            //检查是否存在分组循环嵌套问题
            //获取分组所在的所有分组
            $groupGroupList = $this->SVNAdmin->GetSvnGroupAllGroupList($this->authzContent, $this->payload['svn_group_name']);

            if (in_array($this->payload['objectName'], $groupGroupList)) {
                return message(200, 0, '存在分组循环嵌套的情况');
            }
        }

        funFilePutContents($this->configSvn['svn_authz_file'], $result);

        //修改后同步下
        $this->authzContent = file_get_contents($this->configSvn['svn_authz_file']);
        $syncResult = $this->SyncGroupToDb();
        if ($syncResult['status'] != 1) {
            return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
        }

        return message();
    }
}
