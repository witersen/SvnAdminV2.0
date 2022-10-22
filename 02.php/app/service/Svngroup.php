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
     * SVN分组 => 数据库
     */
    private function SyncGroupToDb()
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
        $sync = $this->payload['sync'];
        $page = $this->payload['page'];

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
        }
        $searchKeyword = trim($this->payload['searchKeyword']);

        if ($page) {
            //分页
            $begin = $pageSize * ($currentPage - 1);
        }

        if ($page) {
            $list = $this->database->select('svn_groups', [
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
            $list = $this->database->select('svn_groups', [
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
        $this->Logs->InsertLog(
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
            sprintf("分组名:%s", $this->payload['svn_group_name']),
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

        funFilePutContents($this->config_svn['svn_authz_file'], $result);

        //修改后同步下
        $this->authzContent = file_get_contents($this->config_svn['svn_authz_file']);
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
            $result[] = [
                'objectType' => 'user',
                'objectName' => $value,
            ];
        }
        foreach ($list['include']['groups']['list'] as $value) {
            $result[] = [
                'objectType' => 'group',
                'objectName' => $value,
            ];
        }
        foreach ($list['include']['aliases']['list'] as $value) {
            $result[] = [
                'objectType' => 'aliase',
                'objectName' => $value,
            ];
        }

        return message(200, 1, '成功', $result);
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

        //修改后同步下
        $this->authzContent = file_get_contents($this->config_svn['svn_authz_file']);
        $syncResult = $this->SyncGroupToDb();
        if ($syncResult['status'] != 1) {
            return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
        }

        return message();
    }
}
