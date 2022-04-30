<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-01 02:04:32
 * @Description: QQ:1801168257
 */

// use SVNAdmin\SVNRep\SVNRep as SVNRepSVNRep;

class svnrep extends controller
{
    private $Svngorup;

    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
        $this->Svngorup = new svngroup();
    }

    /**
     * 新建仓库
     */
    function CreateRep()
    {
        //检查表单
        FunCheckForm($this->requestPayload, [
            'rep_name' => ['type' => 'string', 'notNull' => true],
            'rep_note' => ['type' => 'string', 'notNull' => false],
            'rep_type' => ['type' => 'string', 'notNull' => true],
        ]);

        //检查仓库名是否合法
        FunCheckRepName($this->requestPayload['rep_name']);

        //检查仓库是否存在
        $this->SVNAdminRep->CheckRepExist($this->requestPayload['rep_name']);

        //创建空仓库
        //解决创建中文仓库乱码问题
        FunShellExec('export LC_CTYPE=en_US.UTF-8 &&  svnadmin create ' . SVN_REPOSITORY_PATH .  $this->requestPayload['rep_name']);

        if ($this->requestPayload['rep_type'] == '2') {
            //以指定的目录结构初始化仓库
            $this->SVNAdminRep->InitRepStruct(TEMPLETE_INIT_STRUTCT, SVN_REPOSITORY_PATH . $this->requestPayload['rep_name']);
        }

        //检查是否创建成功
        $this->SVNAdminRep->CheckRepCreate($this->requestPayload['rep_name']);

        //向authz写入仓库信息
        $status = $this->SVNAdminRep->SetRepAuthz($this->globalAuthzContent, $this->requestPayload['rep_name'], '/');
        if ($status != '1') {
            FunShellExec('echo \'' . $status . '\' > ' . SVN_AUTHZ_FILE);
        }

        //写入数据库
        $this->database->insert('svn_reps', [
            'rep_name' => $this->requestPayload['rep_name'],
            'rep_size' => 0,
            'rep_note' => $this->requestPayload['rep_note'],
            'rep_rev' => 0,
            'rep_uuid' => 0
        ]);

        FunMessageExit();
    }

    /**
     * 物理仓库 => svn_reps数据表
     * 
     * 1、将物理仓库存在而没有写入数据库的记录写入数据库
     * 2、将物理仓库已经删除但是数据库依然存在的从数据库删除
     */
    function SyncRepAndDb()
    {
        $svnRepList = $this->SVNAdminRep->GetSimpleRepList();

        $dbRepList = $this->database->select('svn_reps', [
            'rep_name',
        ]);

        foreach ($dbRepList as $value) {
            if (!in_array($value['rep_name'], $svnRepList)) {
                $this->database->delete('svn_reps', [
                    'rep_name' => $value['rep_name']
                ]);
            } else {
                //更新
                $this->database->update('svn_reps', [
                    'rep_size' => FunGetDirSizeDu(SVN_REPOSITORY_PATH .  $value['rep_name']),
                    'rep_rev' => $this->SVNAdminRep->GetRepRev($value['rep_name'])
                ], [
                    'rep_name' => $value['rep_name']
                ]);
            }
        }

        foreach ($svnRepList as $value) {
            if (!in_array($value, FunArrayColumn($dbRepList, 'rep_name'))) {
                $this->database->insert('svn_reps', [
                    'rep_name' => $value,
                    'rep_size' => FunGetDirSizeDu(SVN_REPOSITORY_PATH .  $value),
                    'rep_note' => '',
                    'rep_rev' => $this->SVNAdminRep->GetRepRev($value),
                    'rep_uuid' => ''
                ]);
            }
        }
    }

    /**
     * 物理仓库 => authz文件
     * 
     * 1、将物理仓库已经删除但是authz文件中依然存在的从authz文件删除
     * 2、将在物理仓库存在但是authz文件中不存在的向authz文件写入
     */
    function SyncRepAndAuthz()
    {
        $svnRepList = $this->SVNAdminRep->GetSimpleRepList();

        $svnRepAuthzList = $this->SVNAdminRep->GetNoPathAndConRepAuthz($this->globalAuthzContent);

        $authzContet = $this->globalAuthzContent;

        foreach ($svnRepList as $key => $value) {
            if (!in_array($value, $svnRepAuthzList)) {
                $authzContet = $this->SVNAdminRep->SetRepAuthz($authzContet, $value, '/');
                if ($authzContet == '1') {
                    FunMessageExit(200, 0, '同步到配置文件错误');
                }
            }
        }

        foreach ($svnRepAuthzList as $key => $value) {
            if (!in_array($value, $svnRepList)) {
                $authzContet = $this->SVNAdminRep->DelRepAuthz($authzContet, $value);
                if ($authzContet == '1') {
                    FunMessageExit(200, 0, '同步到配置文件错误');
                }
            }
        }

        if ($authzContet != $this->globalAuthzContent) {
            FunShellExec('echo \'' . $authzContet . '\' > ' . SVN_AUTHZ_FILE);
        }
    }

    /**
     * 对用户有权限的仓库路径列表进行一一验证
     * 
     * 确保该仓库的路径存在于仓库的最新版本库中
     * 
     * 此方式可以清理掉因为目录/文件名进行修改/删除后造成的authz文件冗余 
     * 但是此方式只能清理对此用户进行的有权限的授权 而不能清理无权限的情况
     * 以后有时间会考虑对所有的路径进行扫描和清理[todo]
     */
    function SyncRepPathCheck()
    {
        //获取在authz文件配置的用户有权限的仓库路径列表
        $userRepList = [];

        //获取用户有权限的仓库列表
        $userRepList = array_merge($userRepList, $this->SVNAdminUser->GetUserPriRepListWithPriAndPath($this->globalAuthzContent, $this->globalUserName));

        //获取用户所在的所有分组
        $userGroupList = $this->Svngorup->GetSvnUserAllGroupList($this->globalUserName);

        //获取分组有权限的仓库路径列表
        foreach ($userGroupList as $value) {
            $userRepList = array_merge($userRepList, $this->SVNAdminGroup->GetGroupPriRepListWithPriAndPath($this->globalAuthzContent, $value));
        }

        //按照全路径去重
        $tempArray = [];
        foreach ($userRepList as $key => $value) {
            if (in_array($value['unique'], $tempArray)) {
                unset($userRepList[$key]);
            } else {
                array_push($tempArray, $value['unique']);
            }
        }

        //处理不连续的下标
        $userRepList = array_values($userRepList);

        $authzContent = $this->globalAuthzContent;

        foreach ($userRepList as $key => $value) {
            $cmd = sprintf("svnlook tree  '%s' --full-paths --non-recursive '%s'", SVN_REPOSITORY_PATH .  $value['repName'], $value['priPath']);
            $result = FunShellExec($cmd);

            if (strstr($result['error'], 'svnlook: E160013:')) {
                //路径在仓库不存在
                //从配置文件删除指定仓库的指定路径
                $tempResult = $this->SVNAdminRep->DelRepPath($authzContent, $value['repName'], $value['priPath']);
                if ($tempResult != '1') {
                    $authzContent = $tempResult;
                }
            }
        }

        //写入配置文件
        if ($authzContent != $this->globalAuthzContent) {
            FunShellExec('echo \'' . $authzContent . '\' > ' . SVN_AUTHZ_FILE);
        }
    }

    /**
     * 用户有权限的仓库路径列表 => svn_user_pri_paths数据表
     * 
     * 1、列表中存在的但是数据表不存在则向数据表插入
     * 2、列表中不存在的但是数据表存在从数据表删除
     */
    function SyncUserRepAndDb()
    {
        //获取在authz文件配置的用户有权限的仓库路径列表
        $userRepList = [];

        //获取用户有权限的仓库列表
        $userRepList = array_merge($userRepList, $this->SVNAdminUser->GetUserPriRepListWithPriAndPath($this->globalAuthzContent, $this->globalUserName));

        //获取用户所在的所有分组
        $userGroupList = $this->Svngorup->GetSvnUserAllGroupList($this->globalUserName);

        //获取分组有权限的仓库路径列表
        foreach ($userGroupList as $value) {
            $userRepList = array_merge($userRepList, $this->SVNAdminGroup->GetGroupPriRepListWithPriAndPath($this->globalAuthzContent, $value));
        }

        //按照全路径去重
        $tempArray = [];
        foreach ($userRepList as $key => $value) {
            if (in_array($value['unique'], $tempArray)) {
                unset($userRepList[$key]);
            } else {
                array_push($tempArray, $value['unique']);
            }
        }

        //处理不连续的下标
        $userRepList = array_values($userRepList);

        //从数据库中删除该用户的所有权限
        $this->database->delete('svn_user_pri_paths', [
            'svn_user_name' => $this->globalUserName
        ]);

        //处理数据格式为适合插入数据表的格式
        foreach ($userRepList as $key => $value) {
            $userRepList[$key]['rep_name'] = $value['repName'];
            unset($userRepList[$key]['repName']);

            $userRepList[$key]['pri_path'] = $value['priPath'];
            unset($userRepList[$key]['priPath']);

            $userRepList[$key]['rep_pri'] = $value['repPri'];
            unset($userRepList[$key]['repPri']);

            $userRepList[$key]['svn_user_name'] = $this->globalUserName;
        }

        //向数据库插入该用户的所有权限
        $this->database->insert('svn_user_pri_paths', $userRepList);
    }

    /**
     * 获取仓库列表
     */
    function GetRepList()
    {
        /**
         * 物理仓库 => authz文件
         * 
         * 1、将物理仓库已经删除但是authz文件中依然存在的从authz文件删除
         * 2、将在物理仓库存在但是authz文件中不存在的向authz文件写入
         */
        $this->SyncRepAndAuthz();

        /**
         * 物理仓库 => svn_reps数据表
         * 
         * 1、将物理仓库存在而没有写入数据库的记录写入数据库
         * 2、将物理仓库已经删除但是数据库依然存在的从数据库删除
         */
        $this->SyncRepAndDb();

        $pageSize = $this->requestPayload['pageSize'];
        $currentPage = $this->requestPayload['currentPage'];
        $searchKeyword = trim($this->requestPayload['searchKeyword']);

        //分页
        $begin = $pageSize * ($currentPage - 1);

        $list = $this->database->select('svn_reps', [
            'rep_id',
            'rep_name',
            'rep_size',
            'rep_note',
            'rep_rev',
            'rep_uuid'
        ], [
            'AND' => [
                'OR' => [
                    'rep_name[~]' => $searchKeyword,
                    'rep_note[~]' => $searchKeyword,
                ],
            ],
            'LIMIT' => [$begin, $pageSize],
            'ORDER' => [
                $this->requestPayload['sortName']  => strtoupper($this->requestPayload['sortType'])
            ]
        ]);

        $total = $this->database->count('svn_reps', [
            'rep_id'
        ], [
            'AND' => [
                'OR' => [
                    'rep_name[~]' => $searchKeyword,
                    'rep_note[~]' => $searchKeyword,
                ],
            ],
        ]);

        foreach ($list as $key => $value) {
            $list[$key]['rep_size'] = FunFormatSize($value['rep_size']);
        }

        FunMessageExit(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * SVN用户获取自己有权限的仓库列表
     */
    function GetSvnUserRepList()
    {
        /**
         * 物理仓库 => authz文件
         * 
         * 1、将物理仓库已经删除但是authz文件中依然存在的从authz文件删除
         * 2、将在物理仓库存在但是authz文件中不存在的向authz文件写入
         */
        $this->SyncRepAndAuthz();

        /**
         * 及时更新
         */
        parent::UPdateAuthz();

        /**
         * 对用户有权限的仓库路径列表进行一一验证
         * 
         * 确保该仓库的路径存在于仓库的最新版本库中
         */
        $this->SyncRepPathCheck();

        /**
         * 及时更新
         */
        parent::UPdateAuthz();

        /**
         * 用户有权限的仓库路径列表 => svn_user_pri_paths数据表
         * 
         * 1、列表中存在的但是数据表不存在则向数据表插入
         * 2、列表中不存在的但是数据表存在从数据表删除
         */
        $this->SyncUserRepAndDb();

        $pageSize = $this->requestPayload['pageSize'];
        $currentPage = $this->requestPayload['currentPage'];
        $searchKeyword = trim($this->requestPayload['searchKeyword']);

        //分页
        $begin = $pageSize * ($currentPage - 1);

        $list = $this->database->select('svn_user_pri_paths', [
            'svnn_user_pri_path_id',
            'rep_name',
            'pri_path',
            'rep_pri'
        ], [
            'AND' => [
                'OR' => [
                    'rep_name[~]' => $searchKeyword,
                ],
            ],
            'LIMIT' => [$begin, $pageSize],
            'ORDER' => [
                'rep_name'  => strtoupper($this->requestPayload['sortType'])
            ],
            'svn_user_name' => $this->globalUserName
        ]);

        $total = $this->database->count('svn_user_pri_paths', [
            'svnn_user_pri_path_id'
        ], [
            'AND' => [
                'OR' => [
                    'rep_name[~]' => $searchKeyword,
                ],
            ],
            'svn_user_name' => $this->globalUserName
        ]);

        FunMessageExit(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 修改仓库的备注信息
     */
    function EditRepNote()
    {
        $this->database->update('svn_reps', [
            'rep_note' => $this->requestPayload['rep_note']
        ], [
            'rep_name' => $this->requestPayload['rep_name']
        ]);

        FunMessageExit(200, 1, '已保存');
    }

    /**
     * SVN用户根据目录名称获取该目录下的文件和文件夹列表
     */
    function GetUserRepCon()
    {
        $path = $this->requestPayload['path'];

        $repName = $this->requestPayload['rep_name'];

        /**
         * 获取svn检出地址
         * 
         * 目的为使用当前SVN用户的身份来进行被授权过的路径的内容浏览
         */
        $bindInfo = $this->SVNAdminInfo->GetSubversionListen(SVNSERVE_ENV_FILE, LISTEN_FILE);
        $checkoutHost = 'svn://' . $bindInfo['bindHost'];
        if ($bindInfo['bindPort'] != '3690') {
            $checkoutHost = 'svn://' . $bindInfo['bindHost'] . ':' . $bindInfo['bindPort'];
        }

        /**
         * 获取SVN用户密码
         * 
         * 目的为使用该用户的权限进行操作 确保用户看到的就是所授权的
         */
        $svnUserPass = $this->SVNAdminUser->GetPassByUser($this->globalPasswdContent, $this->globalUserName);
        if ($svnUserPass == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        } else if ($svnUserPass == '1') {
            FunMessageExit(200, 0, '用户不存在' . $this->globalUserName);
        }

        /**
         * 使用svn list进行内容获取
         */
        $result = $this->SVNAdminRep->CheckSvnUserPathAutzh($checkoutHost, $repName, $path, $this->globalUserName, $svnUserPass);

        /**
         * 判断结果是否为空
         * 判断其他的意外情况
         */
        if ($result == '') {
            $resultArray = [];
        } else {
            $resultArray = explode("\n", $result);
        }

        /**
         * 判断该条权限是否为文件授权而不是目录授权
         * 
         * 因为从authz文件返回的授权信息无论是文件还是路径都没有/ 因此需要在该用户有权限的情况下从svn list结果区分
         * 如果结果为一条信息 + 不以/结尾 + 结果名称和请求信息相同，则判断为文件授权 需要另外处理
         */
        $isSingleFilePri = false;
        if ($result != "") {
            if (count($resultArray) == 1) {
                if (substr($result, strlen($result) - 1, 1) != '/') {
                    $tempArray = explode('/', $path);
                    if ($result == $tempArray[count($tempArray) - 1]) {
                        //确定为单文件授权
                        $isSingleFilePri = true;
                    }
                }
            }
        }

        /**
         * 获取版本号等文件详细信息
         * 
         * 此处也要针对但文件授权进行单独处理
         */
        $data = [];
        if ($isSingleFilePri) {
            //获取文件或者文件夹最年轻的版本号
            $lastRev  = $this->SVNAdminRep->GetRepFileRev($repName, $path);

            //获取文件或者文件夹最年轻的版本的作者
            $lastRevAuthor = $this->SVNAdminRep->GetRepFileAuthor($repName, $lastRev);

            //同上 日期
            $lastRevDate = $this->SVNAdminRep->GetRepFileDate($repName, $lastRev);

            //同上 日志
            $lastRevLog = $this->SVNAdminRep->GetRepFileLog($repName, $lastRev);

            array_push($data, [
                'resourceType' => 1,
                'resourceName' => $tempArray[count($tempArray) - 1],
                'fileSize' => $this->SVNAdminRep->GetRepRevFileSize($repName, $path),
                'revAuthor' => $lastRevAuthor,
                'revNum' => 'r' . $lastRev,
                'revTime' => $lastRevDate,
                'revLog' => $lastRevLog,
                'fullPath' => $path
            ]);
        } else {
            foreach ($resultArray as $key => $value) {
                //补全路径
                if (substr($path, strlen($path) - 1, 1) == '/') {
                    $value = $path .  $value;
                } else {
                    $value = $path . '/' . $value;
                }

                //获取文件或者文件夹最年轻的版本号
                $lastRev  = $this->SVNAdminRep->GetRepFileRev($repName, $value);

                //获取文件或者文件夹最年轻的版本的作者
                $lastRevAuthor = $this->SVNAdminRep->GetRepFileAuthor($repName, $lastRev);

                //同上 日期
                $lastRevDate = $this->SVNAdminRep->GetRepFileDate($repName, $lastRev);

                //同上 日志
                $lastRevLog = $this->SVNAdminRep->GetRepFileLog($repName, $lastRev);

                $pathArray = explode('/', $value);
                $pathArray = array_values(array_filter($pathArray, 'FunArrayValueFilter'));
                $pathArrayCount = count($pathArray);
                if (substr($value, strlen($value) - 1, 1) == '/') {
                    array_push($data, [
                        'resourceType' => 2,
                        'resourceName' => $pathArray[$pathArrayCount - 1],
                        'fileSize' => '',
                        'revAuthor' => $lastRevAuthor,
                        'revNum' => 'r' . $lastRev,
                        'revTime' => $lastRevDate,
                        'revLog' => $lastRevLog,
                        'fullPath' => $value
                    ]);
                } else {
                    array_push($data, [
                        'resourceType' => 1,
                        'resourceName' => $pathArray[$pathArrayCount - 1],
                        'fileSize' => $this->SVNAdminRep->GetRepRevFileSize($repName, $value),
                        'revAuthor' => $lastRevAuthor,
                        'revNum' => 'r' . $lastRev,
                        'revTime' => $lastRevDate,
                        'revLog' => $lastRevLog,
                        'fullPath' => $value
                    ]);
                }
            }
        }

        /**
         * 处理面包屑
         */
        if ($path == '/') {
            $breadPathArray = ['/'];
            $breadNameArray = [$repName];
        } else {
            $pathArray = explode('/', $path);
            //将全路径处理为带有/的数组
            $tempArray = [];
            array_push($tempArray, '/');
            for ($i = 0; $i < count($pathArray); $i++) {
                if ($pathArray[$i] != '') {
                    array_push($tempArray, $pathArray[$i]);
                    array_push($tempArray, '/');
                }
            }
            //处理为递增的路径数组
            $breadPathArray = ['/'];
            $breadNameArray = [$repName];
            $tempPath = '/';
            for ($i = 1; $i < count($tempArray); $i += 2) {
                $tempPath .= $tempArray[$i] . $tempArray[$i + 1];
                array_push($breadPathArray, $tempPath);
                array_push($breadNameArray, $tempArray[$i]);
            }
        }

        //针对单文件授权情况进行处理
        if ($isSingleFilePri) {
            unset($breadPathArray[count($breadPathArray) - 1]);
            // unset($breadNameArray[count($breadNameArray) - 1]);
        }

        FunMessageExit(200, 1, '', [
            'data' => $data,
            'bread' => [
                'path' => $breadPathArray,
                'name' => $breadNameArray,
            ]
        ]);
    }

    /**
     * 管理人员根据目录名称获取该目录下的文件和文件夹列表
     */
    function GetRepCon()
    {
        /**
         * 有权限的开始路径
         * 
         * 管理员为 /
         * SVN用户为管理员设定的路径值
         */
        $path = $this->requestPayload['path'];

        //获取全路径的一层目录树
        $cmdSvnlookTree = sprintf("svnlook tree  '%s' --full-paths --non-recursive '%s'", SVN_REPOSITORY_PATH .  $this->requestPayload['rep_name'], $path);
        $result = FunShellExec($cmdSvnlookTree);
        $result = $result['result'];
        $resultArray = explode("\n", trim($result));
        unset($resultArray[0]);
        $resultArray = array_values($resultArray);

        $data = [];
        foreach ($resultArray as $key => $value) {
            //获取文件或者文件夹最年轻的版本号
            $lastRev  = $this->SVNAdminRep->GetRepFileRev($this->requestPayload['rep_name'], $value);

            //获取文件或者文件夹最年轻的版本的作者
            $lastRevAuthor = $this->SVNAdminRep->GetRepFileAuthor($this->requestPayload['rep_name'], $lastRev);

            //同上 日期
            $lastRevDate = $this->SVNAdminRep->GetRepFileDate($this->requestPayload['rep_name'], $lastRev);

            //同上 日志
            $lastRevLog = $this->SVNAdminRep->GetRepFileLog($this->requestPayload['rep_name'], $lastRev);

            $pathArray = explode('/', $value);
            $pathArray = array_values(array_filter($pathArray, 'FunArrayValueFilter'));
            $pathArrayCount = count($pathArray);
            if (substr($value, strlen($value) - 1, 1) == '/') {
                array_push($data, [
                    'resourceType' => 2,
                    'resourceName' => $pathArray[$pathArrayCount - 1],
                    'fileSize' => '',
                    'revAuthor' => $lastRevAuthor,
                    'revNum' => 'r' . $lastRev,
                    'revTime' => $lastRevDate,
                    'revLog' => $lastRevLog,
                    'fullPath' => $value
                ]);
            } else {
                array_push($data, [
                    'resourceType' => 1,
                    'resourceName' => $pathArray[$pathArrayCount - 1],
                    'fileSize' => $this->SVNAdminRep->GetRepRevFileSize($this->requestPayload['rep_name'], $value),
                    'revAuthor' => $lastRevAuthor,
                    'revNum' => 'r' . $lastRev,
                    'revTime' => $lastRevDate,
                    'revLog' => $lastRevLog,
                    'fullPath' => $value
                ]);
            }
        }

        //处理面包屑
        if ($path == '/') {
            $breadPathArray = ['/'];
            $breadNameArray = [$this->requestPayload['rep_name']];
        } else {
            $pathArray = explode('/', $path);
            //将全路径处理为带有/的数组
            $tempArray = [];
            array_push($tempArray, '/');
            for ($i = 0; $i < count($pathArray); $i++) {
                if ($pathArray[$i] != '') {
                    array_push($tempArray, $pathArray[$i]);
                    array_push($tempArray, '/');
                }
            }
            //处理为递增的路径数组
            $breadPathArray = ['/'];
            $breadNameArray = [$this->requestPayload['rep_name']];
            $tempPath = '/';
            for ($i = 1; $i < count($tempArray); $i += 2) {
                $tempPath .= $tempArray[$i] . $tempArray[$i + 1];
                array_push($breadPathArray, $tempPath);
                array_push($breadNameArray, $tempArray[$i]);
            }
        }

        FunMessageExit(200, 1, '', [
            'data' => $data,
            'bread' => [
                'path' => $breadPathArray,
                'name' => $breadNameArray
            ]
        ]);
    }

    /**
     * 根据目录名称获取该目录下的目录树
     * 
     * 管理员配置目录授权用
     */
    function GetRepTree()
    {
        $path = $this->requestPayload['path'];

        //获取全路径的一层目录树
        $cmdSvnlookTree = sprintf("svnlook tree  '%s' --full-paths --non-recursive '%s'", SVN_REPOSITORY_PATH  . $this->requestPayload['rep_name'], $path);
        $result = FunShellExec($cmdSvnlookTree);
        $result = $result['result'];
        $resultArray = explode("\n", trim($result));
        unset($resultArray[0]);
        $resultArray = array_values($resultArray);

        $data = [];
        foreach ($resultArray as $value) {
            $pathArray = explode('/', $value);
            $pathArray = array_values(array_filter($pathArray, 'FunArrayValueFilter'));
            $pathArrayCount = count($pathArray);
            if (substr($value, strlen($value) - 1, 1) == '/') {
                array_push($data, [
                    'expand' => false,
                    'loading' => false,
                    'resourceType' => 2,
                    'title' => $pathArray[$pathArrayCount - 1] . '/',
                    'fullPath' => $value,
                    'children' => []
                ]);
            } else {
                array_push($data, [
                    'resourceType' => 1,
                    'title' => $pathArray[$pathArrayCount - 1],
                    'fullPath' => $value,
                ]);
            }
        }

        //按照文件夹在前、文件在后的顺序进行字典排序
        array_multisort(FunArrayColumn($data, 'resourceType'), SORT_DESC, $data);

        if ($path == '/') {
            FunMessageExit(200, 1, '', [
                [
                    'expand' => true,
                    'loading' => false,
                    'resourceType' => 2,
                    'title' => $this->requestPayload['rep_name'] . '/',
                    'fullPath' => '/',
                    'children' => $data
                ]
            ]);
        } else {
            FunMessageExit(200, 1, '', $data);
        }
    }

    /**
     * 获取某个仓库路径的用户权限列表
     */
    function GetRepPathUserPri()
    {
        $result = $this->SVNAdminRep->GetRepUserListWithPri($this->globalAuthzContent, $this->requestPayload['rep_name'], $this->requestPayload['path']);
        if ($result == '0') {
            //没有该路径的记录
            if ($this->requestPayload['path'] == '/') {
                //不正常 没有写入仓库记录
                FunMessageExit(200, 0, '该仓库没有被写入配置文件！请刷新仓库列表以同步');
            } else {
                //正常 无记录
                FunMessageExit(200, 1, '成功', []);
            }
        } else {
            foreach ($result as $key => $value) {
                $result[$key]['index'] = $key;
                if ($value['userPri'] == '') {
                    $result[$key]['userPri'] = 'no';
                }
            }
            FunMessageExit(200, 1, '成功', $result);
        }
    }

    /**
     * 获取某个仓库路径的分组权限列表
     */
    function GetRepPathGroupPri()
    {
        $result = $this->SVNAdminRep->GetRepGroupListWithPri($this->globalAuthzContent, $this->requestPayload['rep_name'], $this->requestPayload['path']);
        if ($result == '0') {
            //没有该路径的记录
            if ($this->requestPayload['path'] == '/') {
                //不正常 没有写入仓库记录
                FunMessageExit(200, 0, '该仓库没有被写入配置文件！请刷新仓库列表以同步');
            } else {
                //正常 无记录
                FunMessageExit(200, 1, '成功', []);
            }
        } else {
            foreach ($result as $key => $value) {
                $result[$key]['index'] = $key;
                if ($value['groupPri'] == '') {
                    $result[$key]['groupPri'] = 'no';
                }
            }
            FunMessageExit(200, 1, '成功', $result);
        }
    }

    /**
     * 增加某个仓库路径的用户权限
     */
    function AddRepPathUserPri()
    {
        $repName = $this->requestPayload['rep_name'];
        $path = $this->requestPayload['path'];
        $pri = $this->requestPayload['pri'];
        $user = $this->requestPayload['user'];

        /**
         * 这里要进行重复添加用户的判断操作
         * 
         * 如果不对用户重复添加进行限制 则会导致用户的权限被重设为rw
         */
        //todo or no todo

        /**
         * 处理权限
         */
        $pri = $pri == 'no' ? '' : $pri;

        /**
         * 包括为已有权限的用户修改权限
         * 包括为没有权限的用户增加权限
         */
        $result = $this->SVNAdminRep->SetRepUserPri($this->globalAuthzContent, $user, $pri, $repName, $path);

        //没有该仓库路径记录
        if ($result == '0') {

            //没有该仓库路径记录 则进行插入
            $result = $this->SVNAdminRep->SetRepAuthz($this->globalAuthzContent, $repName, $path);

            if ($result == '1') {
                FunMessageExit(200, 1, '未知错误');
            }

            //重新添加权限
            $result = $this->SVNAdminRep->SetRepUserPri($result, $user, $pri, $repName, $path);

            if ($result == '0') {
                FunMessageExit(200, 1, '未知错误');
            }
        }

        //写入
        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        //返回
        FunMessageExit();
    }

    /**
     * 删除某个仓库路径的用户权限
     */
    function DelRepPathUserPri()
    {
        $repName = $this->requestPayload['rep_name'];
        $path = $this->requestPayload['path'];
        $user = $this->requestPayload['user'];

        $result = $this->SVNAdminRep->DelRepUserPri($this->globalAuthzContent, $user, $repName, $path);

        if ($result == '0') {
            FunMessageExit(200, 0, '不存在该仓库路径的记录');
        } else if ($result == '1') {
            FunMessageExit(200, 0, '已被删除');
        } else {
            //写入
            FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

            //返回
            FunMessageExit();
        }
    }

    /**
     * 修改某个仓库路径的用户权限
     */
    function EditRepPathUserPri()
    {
        $repName = $this->requestPayload['rep_name'];
        $path = $this->requestPayload['path'];
        $pri = $this->requestPayload['pri'];
        $user = $this->requestPayload['user'];

        /**
         * 处理权限
         */
        $pri = $pri == 'no' ? '' : $pri;

        $result = $this->SVNAdminRep->UpdRepUserPri($this->globalAuthzContent, $user, $pri, $repName, $path);

        if ($result == '0') {
            FunMessageExit(200, 0, '不存在该仓库路径的记录');
        }

        //写入
        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        //返回
        FunMessageExit();
    }

    /**
     * 增加某个仓库路径的分组权限
     */
    function AddRepPathGroupPri()
    {
        $repName = $this->requestPayload['rep_name'];
        $path = $this->requestPayload['path'];
        $pri = $this->requestPayload['pri'];
        $group = $this->requestPayload['group'];

        /**
         * 这里要进行重复添加分组的判断操作
         * 
         * 如果不对分组重复添加进行限制 则会导致分组的权限被重设为rw
         */
        //todo or no todo

        /**
         * 处理权限
         */
        $pri = $pri == 'no' ? '' : $pri;

        /**
         * 包括为已有权限的分组修改权限
         * 包括为没有权限的分组增加权限
         */
        $result = $this->SVNAdminRep->SetRepGroupPri($this->globalAuthzContent, $group, $pri, $repName, $path);

        //没有该仓库路径记录
        if ($result == '0') {

            //没有该仓库路径记录 则进行插入
            $result = $this->SVNAdminRep->SetRepAuthz($this->globalAuthzContent, $repName, $path);

            if ($result == '1') {
                FunMessageExit(200, 1, '未知错误');
            }

            //重新添加权限
            $result = $this->SVNAdminRep->SetRepGroupPri($result, $group, $pri, $repName, $path);

            if ($result == '0') {
                FunMessageExit(200, 1, '未知错误');
            }
        }

        //写入
        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        //返回
        FunMessageExit();
    }

    /**
     * 删除某个仓库路径的分组权限
     */
    function DelRepPathGroupPri()
    {
        $repName = $this->requestPayload['rep_name'];
        $path = $this->requestPayload['path'];
        $group = $this->requestPayload['group'];

        $result = $this->SVNAdminRep->DelRepGroupPri($this->globalAuthzContent, $group, $repName, $path);

        if ($result == '0') {
            FunMessageExit(200, 0, '不存在该仓库路径的记录');
        } else if ($result == '1') {
            FunMessageExit(200, 0, '已被删除');
        } else {
            //写入
            FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

            //返回
            FunMessageExit();
        }
    }

    /**
     * 修改某个仓库路径的分组权限
     */
    function EditRepPathGroupPri()
    {
        $repName = $this->requestPayload['rep_name'];
        $path = $this->requestPayload['path'];
        $pri = $this->requestPayload['pri'];
        $group = $this->requestPayload['group'];

        /**
         * 处理权限
         */
        $pri = $pri == 'no' ? '' : $pri;

        $result = $this->SVNAdminRep->UpdRepGroupPri($this->globalAuthzContent, $group, $pri, $repName, $path);

        if ($result == '0') {
            FunMessageExit(200, 0, '不存在该仓库路径的记录');
        } else if ($result == '1') {
            FunMessageExit(200, 0, '该仓库下不存在该分组');
        }

        //写入
        FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);

        //返回
        FunMessageExit();
    }

    /**
     * 修改仓库名称
     */
    function EditRepName()
    {
        //检查新仓库名是否合法
        FunCheckRepName($this->requestPayload['new_rep_name']);

        //检查原仓库是否不存在
        $this->SVNAdminRep->CheckRepCreate($this->requestPayload['old_rep_name'], '要修改的仓库不存在');

        //检查新仓库名是否存在
        $this->SVNAdminRep->CheckRepExist($this->requestPayload['new_rep_name'],  '已经存在同名仓库');

        //从仓库目录修改仓库名称
        FunShellExec('mv ' . SVN_REPOSITORY_PATH .  $this->requestPayload['old_rep_name'] . ' ' . SVN_REPOSITORY_PATH . $this->requestPayload['new_rep_name']);

        //检查修改过的仓库名称是否存在
        $this->SVNAdminRep->CheckRepCreate($this->requestPayload['new_rep_name'], '修改仓库名称失败');

        //从数据库修改仓库名称
        $this->database->update('svn_reps', [
            'rep_name' => $this->requestPayload['new_rep_name']
        ], [
            'rep_name' => $this->requestPayload['old_rep_name']
        ]);

        //从配置文件修改仓库名称
        $this->SVNAdminRep->UpdRepAuthz($this->globalAuthzContent, $this->requestPayload['old_rep_name'], $this->requestPayload['new_rep_name']);

        FunMessageExit();
    }

    /**
     * 删除仓库
     */
    function DelRep()
    {
        //从配置文件删除指定仓库的所有路径
        $result = $this->SVNAdminRep->DelRepAuthz($this->globalAuthzContent, $this->requestPayload['rep_name']);
        if ($result != '1') {
            FunShellExec('echo \'' . $result . '\' > ' . SVN_AUTHZ_FILE);
        }

        //从数据库中删除
        $this->database->delete('svn_reps', [
            'rep_name' => $this->requestPayload['rep_name']
        ]);

        //从仓库目录删除仓库文件夹
        FunShellExec('cd ' . SVN_REPOSITORY_PATH . ' && rm -rf ./' . $this->requestPayload['rep_name']);
        $this->SVNAdminRep->CheckRepDelete($this->requestPayload['rep_name']);

        //返回
        FunMessageExit();
    }

    /**
     * 获取仓库的属性内容（key-vlaue的形式）
     */
    function GetRepDetail()
    {
        $result = $this->SVNAdminRep->GetRepDetail($this->requestPayload['rep_name']);
        $resultArray = explode("\n", $result);

        $newArray = [];
        foreach ($resultArray as $key => $value) {
            if (trim($value) != '') {
                $tempArray = explode(':', $value);
                if (count($tempArray) == 2) {
                    array_push($newArray, [
                        'repKey' => $tempArray[0],
                        'repValue' => $tempArray[1],
                    ]);
                }
            }
        }
        FunMessageExit(200, 1, '成功', $newArray);
    }

    /**
     * 获取备份文件夹下的文件列表
     */
    function GetBackupList()
    {
        $result = FunGetDirFileList(SVN_BACHUP_PATH);

        FunMessageExit(200, 1, '成功', $result);
    }

    /**
     * 立即备份当前仓库
     */
    function RepDump()
    {
        $this->SVNAdminRep->RepDump($this->requestPayload['rep_name'], $this->requestPayload['rep_name'] . '_' . date('YmdHis') . '_' . FunGetRandStr() . '.dump');

        FunMessageExit();
    }

    /**
     * 删除备份文件
     */
    function DelRepBackup()
    {
        $this->SVNAdminRep->DelRepBackup($this->requestPayload['fileName']);

        FunMessageExit();
    }

    /**
     * 下载备份文件
     */
    function DownloadRepBackup()
    {
        $this->DownloadRepBackup2();
    }

    /**
     * 下载备份文件
     */
    function DownloadRepBackup1()
    {
        $filePath = SVN_BACHUP_PATH .  $this->requestPayload['fileName'];

        $fileName = $this->requestPayload['fileName'];

        //以只读和二进制模式打开文件
        $fp = @fopen($filePath, 'rb');
        if ($fp) {
            // 获取文件大小
            $file_size = filesize($filePath);

            //告诉浏览器这是一个文件流格式的文件
            header('content-type:application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $fileName);

            // 断点续传
            $range = null;
            if (!empty($_SERVER['HTTP_RANGE'])) {
                $range = $_SERVER['HTTP_RANGE'];
                $range = preg_replace('/[\s|,].*/', '', $range);
                $range = explode('-', substr($range, 6));
                if (count($range) < 2) {
                    $range[1] = $file_size;
                }
                $range = array_combine(array('start', 'end'), $range);
                if (empty($range['start'])) {
                    $range['start'] = 0;
                }
                if (empty($range['end'])) {
                    $range['end'] = $file_size;
                }
            }

            // 使用续传
            if ($range != null) {
                header('HTTP/1.1 206 Partial Content');
                header('Accept-Ranges:bytes');

                // 计算剩余长度
                header(sprintf('content-length:%u', $range['end'] - $range['start']));
                header(sprintf('content-range:bytes %s-%s/%s', $range['start'], $range['end'], $file_size));

                // fp指针跳到断点位置
                fseek($fp, sprintf('%u', $range['start']));
            } else {
                header('HTTP/1.1 200 OK');
                header('Accept-Ranges:bytes');
                header('content-length:' . $file_size);
            }
            while (!feof($fp)) {
                echo fread($fp, 4096);
                ob_flush();
            }
            fclose($fp);
        }
    }

    /**
     * 下载备份文件
     */
    function DownloadRepBackup2()
    {
        $filePath = SVN_BACHUP_PATH .  $this->requestPayload['fileName'];

        //文件类型
        $mimeType = 'application/octet-stream';

        //请求区域
        $range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;

        set_time_limit(0);

        require_once BASE_PATH . '/extension/Download/download.class.php';

        $transfer = new Transfer($filePath, $mimeType, $range);

        $transfer->send();
    }

    /**
     * 上传文件到备份文件夹
     */
    function UploadBackup()
    {

        if (array_key_exists('file', $_FILES)) {
            //扩展名
            $fileType =  substr(strrchr($_FILES['file']['name'], '.'), 1);

            //文件名
            $fileName = $_FILES['file']['name'];

            //备份文件夹
            $localFilePath = SVN_BACHUP_PATH .  $fileName;

            //保存
            $cmd = sprintf("mv '%s' '%s'", $_FILES['file']['tmp_name'], $localFilePath);
            FunShellExec($cmd);
            // move_uploaded_file($_FILES['file']['tmp_name'], $localFilePath);

            FunMessageExit();
        } else {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }
    }

    /**
     * 从本地备份文件导入仓库
     */
    function ImportRep()
    {
        //检查备份文件是否存在
        if (!file_exists(SVN_BACHUP_PATH .  $this->requestPayload['fileName'])) {
            FunMessageExit(200, 0, '备份文件不存在');
        }

        //检查操作的仓库是否存在
        $this->SVNAdminRep->CheckRepCreate($this->requestPayload['rep_name'], '仓库不存在');

        //使用svndump
        $result = $this->SVNAdminRep->RepLoad($this->requestPayload['rep_name'], $this->requestPayload['fileName']);

        if ($result['error'] == '') {
            FunMessageExit();
        } else {
            FunMessageExit(200, 0, '导入错误', $result['error']);
        }
    }

    /**
     * 获取仓库的钩子和对应的内容列表
     */
    function GetRepHooks()
    {
        //检查仓库是否存在
        $this->SVNAdminRep->CheckRepCreate($this->requestPayload['rep_name'], '仓库不存在');

        clearstatcache();
        if (!is_dir(SVN_REPOSITORY_PATH .  $this->requestPayload['rep_name'] . '/' . 'hooks')) {
            FunMessageExit(200, 0, '仓库不存在或文件损坏');
        }

        $hooks_type_list =  [
            "start-commit" =>  [
                "value" => "start-commit",
                "label" => "start-commit---事务创建前",
                "shell" => ""
            ],
            "pre-commit" =>  [
                "value" => "pre-commit",
                "label" => "pre-commit---事务提交前",
                "shell" => ""
            ],
            "post-commit" =>  [
                "value" => "post-commit",
                "label" => "post-commit---事务提交后",
                "shell" => ""
            ],
            "pre-lock" =>  [
                "value" => "pre-lock",
                "label" => "pre-lock---锁定文件前",
                "shell" => ""
            ],
            "post-lock" =>  [
                "value" => "post-lock",
                "label" => "post-lock---锁定文件后",
                "shell" => ""
            ],
            "pre-unlock" =>  [
                "value" => "pre-unlock",
                "label" => "pre-unlock---解锁文件前",
                "shell" => ""
            ],
            "post-unlock" =>  [
                "value" => "post-unlock",
                "label" => "post-unlock---解锁文件后",
                "shell" => ""
            ],
            "pre-revprop-change" =>  [
                "value" => "pre-revprop-change",
                "label" => "pre-revprop-change---修改修订版属性前",
                "shell" => ""
            ],
            "post-revprop-change" =>  [
                "value" => "post-revprop-change",
                "label" => "post-revprop-change---修改修订版属性后",
                "shell" => ""
            ],
        ];

        $hooks_file_list = [
            'start-commit',
            'pre-commit',
            'post-commit',
            'pre-lock',
            'post-lock',
            'pre-unlock',
            'post-unlock',
            'pre-revprop-change',
            'post-revprop-change'
        ];

        $file_arr = scandir(SVN_REPOSITORY_PATH .  $this->requestPayload['rep_name'] . '/' . 'hooks');

        foreach ($file_arr as $file_item) {
            if ($file_item != '.' && $file_item != '..') {
                if (in_array($file_item, $hooks_file_list)) {
                    $temp = FunShellExec(sprintf("cat '%s'", SVN_REPOSITORY_PATH .  $this->requestPayload['rep_name'] . '/' . 'hooks' . '/' . $file_item));
                    $hooks_type_list[$file_item]['shell'] = $temp['result'];
                    $hooks_type_list[$file_item]['shell'] = trim($hooks_type_list[$file_item]['shell']);
                }
            }
        }

        FunMessageExit(200, 1, '成功', $hooks_type_list);
    }

    /**
     * 修改仓库的钩子内容（针对单个钩子）
     */
    function EditRepHook()
    {
        $cmd = sprintf("echo '%s' > '%s'", trim($this->requestPayload['content']), SVN_REPOSITORY_PATH .  $this->requestPayload['rep_name'] . '/hooks/' . $this->requestPayload['type']);

        FunShellExec($cmd);

        FunMessageExit();
    }
}
