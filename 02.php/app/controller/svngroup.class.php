<?php

class svngroup extends controller
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
     * 将SVN分组数据同步到数据库
     * 
     * 同时同步每个分组包含的用户数量
     */
    function SyncGroupToDb()
    {
        $svnAndGroupList =  FunGetSvnGroupUserAndGroupList($this->globalAuthzContent);



        if ($svnAndGroupList == 0) {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }

        $svnGroupList = FunArrayColumn($svnAndGroupList, 'groupName');

        $dbGroupPassList = $this->database->select('svn_groups', [
            'svn_group_id',
            'svn_group_name',
            'svn_group_note',
            'include_user_count',
            'include_group_count'
        ]);

        $combinArray = array_combine(FunArrayColumn($svnAndGroupList, 'groupName'), FunArrayColumn($svnAndGroupList, 'include'));

        // FunMessageExit(200,0,'调试',$combinArray);

        foreach ($dbGroupPassList as $key => $value) {
            if (!in_array($value['svn_group_name'], $svnGroupList)) {
                $this->database->delete('svn_groups', [
                    'svn_group_name' => $value['svn_group_name']
                ]);
            } else {
                //更新数量
                $this->database->update('svn_groups', [
                    'include_user_count' => count($combinArray[$value['svn_group_name']]['users']),
                    'include_group_count' => count($combinArray[$value['svn_group_name']]['groups']),
                ], [
                    'svn_group_name' => $value['svn_group_name']
                ]);
            }
        }

        foreach ($svnGroupList as $key => $value) {
            if (!in_array($value, FunArrayColumn($dbGroupPassList, 'svn_group_name'))) {
                $this->database->insert('svn_groups', [
                    'svn_group_name' => $value,
                    'include_user_count' => count($svnAndGroupList[$key]['include']['users']),
                    'include_group_count' => count($svnAndGroupList[$key]['include']['groups']),
                    'svn_group_note' => '',
                ]);
            }
        }
    }

    /**
     * 获取所有的分组列表
     */
    function GetAllGroupList()
    {
        $svnGroupList = FunGetSvnGroupList($this->globalAuthzContent);
        if ($svnGroupList == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        } else {
            $newArray = [];
            foreach ($svnGroupList as $key => $value) {
                array_push($newArray, [
                    'groupName' => $value
                ]);
            }
            FunMessageExit(200, 1, '成功', $newArray);
        }
    }

    /**
     * 获取带有分页的分组列表
     */
    function GetGroupList()
    {
        //同步
        $this->SyncGroupToDb();

        $pageSize = $this->requestPayload['pageSize'];
        $currentPage = $this->requestPayload['currentPage'];
        $searchKeyword = trim($this->requestPayload['searchKeyword']);

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
                $this->requestPayload['sortName']  => strtoupper($this->requestPayload['sortType'])
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

        FunMessageExit(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 编辑分组备注信息
     */
    function EditGroupNote()
    {
        $this->database->update('svn_groups', [
            'svn_group_note' => $this->requestPayload['svn_group_note']
        ], [
            'svn_group_name' => $this->requestPayload['svn_group_name']
        ]);

        FunMessageExit();
    }

    /**
     * 新建SVN分组
     */
    function CreateGroup()
    {
        //检查分组名是否合法
        FunCheckRepGroup($this->requestPayload['svn_group_name']);

        //检查用户是否已存在
        $result = FunAddSvnGroup($this->globalAuthzContent, $this->requestPayload['svn_group_name']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '分组已存在');
        }

        //写入配置文件
        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        //写入数据库
        $this->database->insert('svn_groups', [
            'svn_group_name' => $this->requestPayload['svn_group_name'],
            'include_user_count' => 0,
            'include_group_count' => 0,
            'svn_group_note' => ''
        ]);

        FunMessageExit();
    }

    /**
     * 删除SVN分组
     */
    function DelGroup()
    {
        //从authz文件删除
        $result = FunDelSvnGroup($this->globalAuthzContent, $this->requestPayload['svn_group_name']);

        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '分组不存在');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        //从数据库删除
        $this->database->delete('svn_groups', [
            'svn_group_name' => $this->requestPayload['svn_group_name']
        ]);

        FunMessageExit();
    }

    /**
     * 修改SVN分组的名称
     */
    function EditGroupName()
    {
        //新分组名称是否合法
        FunCheckRepGroup($this->requestPayload['groupNameNew']);

        $svnGroupList = FunGetSvnGroupList($this->globalAuthzContent);

        //旧分组是否存在
        if (!in_array($this->requestPayload['groupNameOld'], $svnGroupList)) {
            FunMessageExit(200, 0, '当前分组不存在');
        }

        //新分组名称是否冲突
        if (in_array($this->requestPayload['groupNameNew'], $svnGroupList)) {
            FunMessageExit(200, 0, '要修改的分组名称已经存在');
        }

        $result = FunUpdSvnGroup($this->globalAuthzContent, $this->requestPayload['groupNameOld'], $this->requestPayload['groupNameNew']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        FunMessageExit();
    }

    /**
     * 获取SVN分组的用户成员和分组成员
     */
    function GetGroupMember()
    {
        $memberUserList = FunGetSvnUserListByGroup($this->globalAuthzContent, $this->requestPayload['svn_group_name']);

        $memberGroupList = FunGetSvnGroupListByGroup($this->globalAuthzContent, $this->requestPayload['svn_group_name']);

        $allGroupList = FunGetSvnGroupList($this->globalAuthzContent);

        $allUserList = FunGetSvnUserList($this->globalPasswdContent);

        if ($memberUserList == '0' || $memberGroupList == '0' || $allGroupList == '0' || $allUserList == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }
        if ($memberUserList == '1' || $memberGroupList == '1' || $allUserList == '1') {
            FunMessageExit(200, 0, '分组不存在');
        }

        $group1 = [];
        foreach ($allGroupList as $key => $value) {
            if (in_array($value, $memberGroupList)) {
                array_push($group1, [
                    'groupName' => $value,
                    'isMember' => true
                ]);
            } else {
                array_push($group1, [
                    'groupName' => $value,
                    'isMember' => false
                ]);
            }
        }

        //排序
        array_multisort(FunArrayColumn($group1, 'isMember'), SORT_DESC, $group1);

        $group2 = [];
        foreach ($allUserList as $key => $value) {
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
        array_multisort(FunArrayColumn($group2, 'isMember'), SORT_DESC, $group2);

        FunMessageExit(200, 1, '成功', [
            'userList' => $group2,
            'groupList' => $group1
        ]);
    }

    /**
     * 将用户添加为SVN分组的成员
     */
    function GroupAddUser()
    {
        $result = FunAddSvnGroupUser($this->globalAuthzContent, $this->requestPayload['svn_group_name'], $this->requestPayload['svn_user_name']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '分组不存在');
        }
        if ($result == '2') {
            FunMessageExit(200, 0, '要添加的用户已存在该分组');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        FunMessageExit();
    }

    /**
     * 将用户从SVN分组的成员移除
     */
    function GroupRemoveUser()
    {
        $result = FunDelSvnGroupUser($this->globalAuthzContent, $this->requestPayload['svn_group_name'], $this->requestPayload['svn_user_name']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '分组不存在');
        }
        if ($result == '2') {
            FunMessageExit(200, 0, '要删除的用户不在该分组');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        FunMessageExit();
    }

    /**
     * 将分组添加为SVN分组的成员
     */
    function GroupAddGroup()
    {
        $result = FunAddSvnGroupGroup($this->globalAuthzContent, $this->requestPayload['svn_group_name'], $this->requestPayload['svn_group_name_add']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '分组不存在');
        }
        if ($result == '2') {
            FunMessageExit(200, 0, '要添加的分组已存在该分组');
        }
        if ($result == '3') {
            FunMessageExit(200, 0, '不能添加本身');
        }

        //检查是否存在分组循环嵌套问题
        //获取分组所在的所有分组
        $groupGroupList = $this->GetSvnGroupAllGroupList($this->requestPayload['svn_group_name']);

        if (in_array($this->requestPayload['svn_group_name_add'], $groupGroupList)) {
            FunMessageExit(200, 0, '存在分组循环嵌套的情况');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        FunMessageExit();
    }

    /**
     * 将分组从SVN分组的成员移除
     */
    function GroupRemoveGroup()
    {
        $result = FunDelSvnGroupGroup($this->globalAuthzContent, $this->requestPayload['svn_group_name'], $this->requestPayload['svn_group_name_del']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '分组不存在');
        }
        if ($result == '2') {
            FunMessageExit(200, 0, '要删除的分组不在该分组');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        FunMessageExit();
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
    function GetSvnUserAllGroupList($userName)
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
    function GetSvnGroupAllGroupList($groupName)
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
}
