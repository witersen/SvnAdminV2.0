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

class Svngroup extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $ServiceLogs;
    private $ServiceLdap;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->ServiceLogs = new ServiceLogs($parm);
        $this->ServiceLdap = new ServiceLdap($parm);
    }

    /**
     * 执行同步
     * 
     * 为用户提供可选项 选择是否清理之前的分组和用户的权限 todo
     *
     * @return array
     */
    public function SyncGroup()
    {
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }

        if ($dataSource['user_source'] == 'ldap' && $dataSource['group_source'] == 'ldap') {
            $result = $this->ServiceLdap->SyncLdapToAuthz();
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message'], $result['data']);
            }

            $result = $this->SyncAuthzToDb();
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message'], $result['data']);
            }
        } else {
            $result = $this->SyncAuthzToDb();
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message'], $result['data']);
            }
        }

        return message();
    }

    /**
     * 分组(authz) => db
     *
     * @return array
     */
    private function SyncAuthzToDb()
    {
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
            $syncResult = $this->SyncGroup();
            if ($syncResult['status'] != 1) {
                return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
            }
        }

        if ($page) {
            $pageSize = $this->payload['pageSize'];
            $currentPage = $this->payload['currentPage'];
            $begin = $pageSize * ($currentPage - 1);
        }

        $result = $this->database->select('svn_groups', [
            'svn_group_id',
            'svn_group_name',
            'svn_group_note',
            'include_user_count [Int]',
            'include_group_count [Int]',
            'include_aliase_count [Int]',
        ], [
            'ORDER' => [
                $this->payload['sortName']  => strtoupper($this->payload['sortType'])
            ]
        ]);

        //过滤
        if (!empty($searchKeyword)) {
            foreach ($result as $key => $value) {
                if (
                    strstr($value['svn_group_name'], $searchKeyword) === false &&
                    strstr($value['svn_group_note'], $searchKeyword) === false
                ) {
                    unset($result[$key]);
                }
            }
            $result = array_values($result);
        }

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
            $result = array_values($result);
        }

        //总计
        $total = empty($result) ? 0 : count($result);

        //分页
        if ($page) {
            $result = array_slice($result, $begin, $pageSize);
        }

        return message(200, 1, '成功', [
            'data' => array_values($result),
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
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }

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
            } elseif ($result == 820) {
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
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }

        if ($dataSource['user_source'] == 'ldap' && $dataSource['group_source'] == 'ldap') {
            return message(200, 0, '当前SVN分组来源为LDAP-不支持此操作');
        }

        //从authz文件删除
        $result = $this->SVNAdmin->DelObjectFromAuthz($this->authzContent, $this->payload['svn_group_name'], 'group');
        if (is_numeric($result)) {
            if ($result == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } elseif ($result == 901) {
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
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }

        if ($dataSource['user_source'] == 'ldap' && $dataSource['group_source'] == 'ldap') {
            return message(200, 0, '当前SVN分组来源为LDAP-不支持此操作');
        }

        //新分组名称是否合法
        $checkResult = $this->checkService->CheckRepGroup($this->payload['groupNameNew']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //修改前同步下
        $syncResult = $this->SyncGroup();
        if ($syncResult['status'] != 1) {
            return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
        }

        $result = $this->SVNAdmin->UpdObjectFromAuthz($this->authzContent, $this->payload['groupNameOld'], $this->payload['groupNameNew'], 'group');
        if (is_numeric($result)) {
            if ($result == 611) {
                return message(200, 0, 'authz文件格式错误(不存在[aliases]标识)');
            } elseif ($result == 612) {
                return message(200, 0, 'authz文件格式错误(不存在[groups]标识)');
            } elseif ($result == 901) {
                return message(200, 0, '不支持的授权对象类型');
            } elseif ($result == 821) {
                return message(200, 0, '要修改的新分组已经存在');
            } elseif ($result == 831) {
                return message(200, 0, '要修改的新别名已经存在');
            } elseif ($result == 731) {
                return message(200, 0, '要修改的别名不存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        funFilePutContents($this->configSvn['svn_authz_file'], $result);

        //修改后同步下
        parent::RereadAuthz();
        $result = $this->SyncGroup();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
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
            } elseif ($list == 720) {
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
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }

        if ($dataSource['user_source'] == 'ldap' && $dataSource['group_source'] == 'ldap') {
            return message(200, 0, '当前SVN分组来源为LDAP-不支持此操作');
        }

        $result = $this->SVNAdmin->UpdGroupMember($this->authzContent, $this->payload['svn_group_name'], $this->payload['objectName'], $this->payload['objectType'], $this->payload['actionType']);
        if (is_numeric($result)) {
            if ($result == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } elseif ($result == 720) {
                return message(200, 0, '分组不存在');
            } elseif ($result == 803) {
                return message(200, 0, '要添加的对象已存在该分组');
            } elseif ($result == 703) {
                return message(200, 0, '要删除的对象不存在该分组');
            } elseif ($result == 901) {
                return message(200, 0, '无效的对象类型 user|group|aliase');
            } elseif ($result == 902) {
                return message(200, 0, '无效的操作类型 add|delete');
            } elseif ($result == 802) {
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
        parent::RereadAuthz();
        $syncResult = $this->SyncGroup();
        if ($syncResult['status'] != 1) {
            return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
        }

        return message();
    }
}
