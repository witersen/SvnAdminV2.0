<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-29 00:42:13
 * @Description: QQ:1801168257
 */

namespace app\service;

class Svngroup extends Base
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
     * 将SVN分组数据同步到数据库
     * 
     * 同时同步每个分组包含的用户数量
     */
    private function SyncGroupToDb()
    {
        $svnAndGroupList = $this->SVNAdmin->GetGroupInfo($this->authzContent);

        if (is_numeric($svnAndGroupList)) {
            if ($svnAndGroupList == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } else {
                return message(200, 0, "错误码$svnAndGroupList");
            }
        }

        $svnGroupList = array_column($svnAndGroupList, 'groupName');

        $dbGroupPassList = $this->database->select('svn_groups', [
            'svn_group_id',
            'svn_group_name',
            'svn_group_note',
            'include_user_count',
            'include_group_count'
        ]);

        $combinArray = array_combine($svnGroupList, array_column($svnAndGroupList, 'include'));

        foreach ($dbGroupPassList as $key => $value) {
            if (!in_array($value['svn_group_name'], $svnGroupList)) {
                $this->database->delete('svn_groups', [
                    'svn_group_name' => $value['svn_group_name']
                ]);
            } else {
                //更新数量
                $this->database->update('svn_groups', [
                    'include_user_count' => $combinArray[$value['svn_group_name']]['users']['count'],
                    'include_group_count' => $combinArray[$value['svn_group_name']]['groups']['count'],
                    // 'include_aliase_count' => $combinArray[$value['svn_group_name']]['aliases']['count'], //todo 增加字段
                ], [
                    'svn_group_name' => $value['svn_group_name']
                ]);
            }
        }

        foreach ($svnGroupList as $key => $value) {
            if (!in_array($value, array_column($dbGroupPassList, 'svn_group_name'))) {
                $this->database->insert('svn_groups', [
                    'svn_group_name' => $value,
                    'include_user_count' => $svnAndGroupList[$key]['include']['users']['count'],
                    'include_group_count' => $svnAndGroupList[$key]['include']['groups']['count'],
                    // 'include_aliase_count' => $svnAndGroupList[$key]['include']['aliases']['count'], //todo 增加字段
                    'svn_group_note' => '',
                ]);
            }
        }
    }

    /**
     * 获取所有的分组列表
     */
    public function GetAllGroupList()
    {
        $searchKeyword = trim($this->payload['searchKeywordGroup']);

        $list = $this->database->select('svn_groups', [
            'svn_group_id',
            'svn_group_name',
            'svn_group_note',
        ], [
            'AND' => [
                'OR' => [
                    'svn_group_name[~]' => $searchKeyword,
                    'svn_group_note[~]' => $searchKeyword,
                ],
            ],
        ]);

        return message(200, 1, '成功', $list);
    }

    /**
     * 获取带有分页的分组列表
     */
    public function GetGroupList()
    {
        //同步
        $this->SyncGroupToDb();

        $pageSize = $this->payload['pageSize'];
        $currentPage = $this->payload['currentPage'];
        $searchKeyword = trim($this->payload['searchKeyword']);

        //分页
        $begin = $pageSize * ($currentPage - 1);

        $list = $this->database->select('svn_groups', [
            'svn_group_id',
            'svn_group_name',
            'svn_group_note',
            'include_user_count',
            'include_group_count'
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

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 编辑分组备注信息
     */
    public function EditGroupNote()
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
        //检查分组名是否合法
        $checkResult = $this->checkService->CheckRepGroup($this->payload['svn_group_name']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //检查用户是否已存在
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
        funFilePutContents($this->config_svn['svn_authz_file'], $result);

        //写入数据库
        $this->database->insert('svn_groups', [
            'svn_group_name' => $this->payload['svn_group_name'],
            'include_user_count' => 0,
            'include_group_count' => 0,
            'svn_group_note' => $this->payload['svn_group_note'],
        ]);

        //日志
        $this->Logs->InsertLog(
            '创建分组',
            sprintf("分组名 %s", $this->payload['svn_group_name']),
            $this->userName
        );

        return message();
    }

    /**
     * 删除SVN分组
     */
    public function DelGroup()
    {
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

        funFilePutContents($this->config_svn['svn_authz_file'], $result);

        //从数据库删除
        $this->database->delete('svn_groups', [
            'svn_group_name' => $this->payload['svn_group_name']
        ]);

        //日志
        $this->Logs->InsertLog(
            '删除分组',
            sprintf("分组名 %s", $this->payload['svn_group_name']),
            $this->userName
        );

        return message();
    }

    /**
     * 修改SVN分组的名称
     */
    public function EditGroupName()
    {
        //新分组名称是否合法
        $checkResult = $this->checkService->CheckRepGroup($this->payload['groupNameNew']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
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

        funFilePutContents($this->config_svn['svn_authz_file'], $result);

        return message();
    }

    /**
     * 获取SVN分组的用户成员和分组成员
     */
    public function GetGroupMember()
    {
        $result = $this->SVNAdmin->GetGroupInfo($this->authzContent, $this->payload['svn_group_name']);
        if (is_numeric($result)) {
            if ($result == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } else if ($result == 720) {
                return message(200, 0, '指定的分组不存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        $allGroupList = $this->SVNAdmin->GetGroupInfo($this->authzContent);
        $allUserList = $this->SVNAdmin->GetUserInfo($this->passwdContent);
        if (is_numeric($allUserList)) {
            if ($allUserList == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else {
                return message(200, 0, "错误码$allUserList");
            }
        }

        $memberUserList = $result['include']['users']['list'];
        $memberGroupList = $result['include']['groups']['list'];

        $group1 = [];
        foreach ($allGroupList as $value) {
            if (in_array($value['groupName'], $memberGroupList)) {
                array_push($group1, [
                    'groupName' => $value['groupName'],
                    'isMember' => true
                ]);
            } else {
                array_push($group1, [
                    'groupName' => $value['groupName'],
                    'isMember' => false
                ]);
            }
        }

        //排序
        // array_multisort(array_column($group1, 'isMember'), SORT_DESC, $group1);

        $group2 = [];
        foreach ($allUserList as $value) {
            if (in_array($value['userName'], $memberUserList)) {
                array_push($group2, [
                    'userName' => $value['userName'],
                    'isMember' => true,
                    'disabled' => $value['disabled']
                ]);
            } else {
                array_push($group2, [
                    'userName' => $value['userName'],
                    'isMember' => false,
                    'disabled' => $value['disabled']
                ]);
            }
        }

        //排序
        // array_multisort(array_column($group2, 'isMember'), SORT_DESC, $group2);

        return message(200, 1, '成功', [
            'userList' => $group2,
            'groupList' => $group1
        ]);
    }

    /**
     * 为分组添加或者删除所包含的对象
     * 对象包括：用户、分组、用户别名
     */
    public function UpdGroupMember()
    {
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

        funFilePutContents($this->config_svn['svn_authz_file'], $result);

        return message();
    }
}
