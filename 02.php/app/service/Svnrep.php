<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-11 02:19:00
 * @Description: QQ:1801168257
 */

namespace app\service;

use Transfer;

class Svnrep extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $Svngroup;
    private $Logs;

    function __construct()
    {
        parent::__construct();

        $this->Svngroup = new Svngroup();
        $this->Svn = new Svn();
        $this->Logs = new Logs();
    }

    /**
     * 新建仓库
     */
    public function CreateRep()
    {
        //检查表单
        $checkResult = FunCheckForm($this->payload, [
            'rep_name' => ['type' => 'string', 'notNull' => true],
            'rep_note' => ['type' => 'string', 'notNull' => false],
            'rep_type' => ['type' => 'string', 'notNull' => true],
        ]);
        if (!$checkResult) {
            return message(200, 0, '参数不完整');
        }

        //检查仓库名是否合法
        $checkResult = $this->checkService->CheckRepName($this->payload['rep_name']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //检查仓库是否存在
        $checkResult = $this->SVNAdminRep->CheckRepExist($this->payload['rep_name']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //创建空仓库
        //解决创建中文仓库乱码问题
        $cmd = sprintf("export LC_CTYPE=en_US.UTF-8 &&  '%s' create " . $this->config_svn['rep_base_path'] .  $this->payload['rep_name'], $this->config_bin['svnadmin']);
        FunShellExec($cmd);

        if ($this->payload['rep_type'] == '2') {
            //以指定的目录结构初始化仓库
            $this->SVNAdminRep->InitRepStruct($this->config_svn['templete_init_struct'], $this->config_svn['rep_base_path'] . $this->payload['rep_name']);
        }

        //检查是否创建成功
        $checkResult = $this->SVNAdminRep->CheckRepCreate($this->payload['rep_name']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //向authz写入仓库信息
        $status = $this->SVNAdminRep->SetRepAuthz($this->authzContent, $this->payload['rep_name'], '/');
        if ($status != '1') {
            // FunShellExec('echo \'' . $status . '\' > ' . $this->config_svn['svn_authz_file']);
            
            FunFilePutContents($this->config_svn['svn_authz_file'], $status);
        }

        //写入数据库
        $this->database->insert('svn_reps', [
            'rep_name' => $this->payload['rep_name'],
            'rep_size' => 0,
            'rep_note' => $this->payload['rep_note'],
            'rep_rev' => 0,
            'rep_uuid' => 0
        ]);

        //日志
        $this->Logs->InsertLog(
            '创建仓库',
            sprintf("仓库名 %s", $this->payload['rep_name']),
            $this->userName
        );

        return message();
    }

    /**
     * 物理仓库 => svn_reps数据表
     * 
     * 1、将物理仓库存在而没有写入数据库的记录写入数据库
     * 2、将物理仓库已经删除但是数据库依然存在的从数据库删除
     */
    private function SyncRepAndDb()
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
                    'rep_size' => FunGetDirSizeDu($this->config_svn['rep_base_path'] .  $value['rep_name']),
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
                    'rep_size' => FunGetDirSizeDu($this->config_svn['rep_base_path'] .  $value),
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
    private function SyncRepAndAuthz()
    {
        $svnRepList = $this->SVNAdminRep->GetSimpleRepList();

        $svnRepAuthzList = $this->SVNAdminRep->GetNoPathAndConRepAuthz($this->authzContent);

        $authzContet = $this->authzContent;

        foreach ($svnRepList as $key => $value) {
            if (!in_array($value, $svnRepAuthzList)) {
                $authzContet = $this->SVNAdminRep->SetRepAuthz($authzContet, $value, '/');
                if ($authzContet == '1') {
                    return message(200, 0, '同步到配置文件错误');
                }
            }
        }

        foreach ($svnRepAuthzList as $key => $value) {
            if (!in_array($value, $svnRepList)) {
                $authzContet = $this->SVNAdminRep->DelRepAuthz($authzContet, $value);
                if ($authzContet == '1') {
                    return message(200, 0, '同步到配置文件错误');
                }
            }
        }

        if ($authzContet != $this->authzContent) {
            // FunShellExec('echo \'' . $authzContet . '\' > ' . $this->config_svn['svn_authz_file']);

            FunFilePutContents($this->config_svn['svn_authz_file'], $authzContet);
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
    private function SyncRepPathCheck()
    {
        //获取在authz文件配置的用户有权限的仓库路径列表
        $userRepList = [];

        //获取用户有权限的仓库列表
        $userRepList = array_merge($userRepList, $this->SVNAdminUser->GetUserPriRepListWithPriAndPath($this->authzContent, $this->userName));

        //获取用户所在的所有分组
        $userGroupList = $this->Svngroup->GetSvnUserAllGroupList($this->userName);

        //获取分组有权限的仓库路径列表
        foreach ($userGroupList as $value) {
            $userRepList = array_merge($userRepList, $this->SVNAdminGroup->GetGroupPriRepListWithPriAndPath($this->authzContent, $value));
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

        $authzContent = $this->authzContent;

        foreach ($userRepList as $key => $value) {
            $cmd = sprintf("'%s' tree  '%s' --full-paths --non-recursive '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path'] .  $value['repName'], $value['priPath']);
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
        if ($authzContent != $this->authzContent) {
            // FunShellExec('echo \'' . $authzContent . '\' > ' . $this->config_svn['svn_authz_file']);

            FunFilePutContents($this->config_svn['svn_authz_file'], $authzContent);
        }
    }

    /**
     * 用户有权限的仓库路径列表 => svn_user_pri_paths数据表
     * 
     * 1、列表中存在的但是数据表不存在则向数据表插入
     * 2、列表中不存在的但是数据表存在从数据表删除
     */
    private function SyncUserRepAndDb()
    {
        //获取在authz文件配置的用户有权限的仓库路径列表
        $userRepList = [];

        //获取用户有权限的仓库列表
        $userRepList = array_merge($userRepList, $this->SVNAdminUser->GetUserPriRepListWithPriAndPath($this->authzContent, $this->userName));

        //获取用户所在的所有分组
        $userGroupList = $this->Svngroup->GetSvnUserAllGroupList($this->userName);

        //获取分组有权限的仓库路径列表
        foreach ($userGroupList as $value) {
            $userRepList = array_merge($userRepList, $this->SVNAdminGroup->GetGroupPriRepListWithPriAndPath($this->authzContent, $value));
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
            'svn_user_name' => $this->userName
        ]);

        //处理数据格式为适合插入数据表的格式
        foreach ($userRepList as $key => $value) {
            $userRepList[$key]['rep_name'] = $value['repName'];
            unset($userRepList[$key]['repName']);

            $userRepList[$key]['pri_path'] = $value['priPath'];
            unset($userRepList[$key]['priPath']);

            $userRepList[$key]['rep_pri'] = $value['repPri'];
            unset($userRepList[$key]['repPri']);

            $userRepList[$key]['svn_user_name'] = $this->userName;
        }

        //向数据库插入该用户的所有权限
        $this->database->insert('svn_user_pri_paths', $userRepList);
    }

    /**
     * 获取仓库列表
     */
    public function GetRepList()
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

        $pageSize = $this->payload['pageSize'];
        $currentPage = $this->payload['currentPage'];
        $searchKeyword = trim($this->payload['searchKeyword']);

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
                $this->payload['sortName']  => strtoupper($this->payload['sortType'])
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

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * SVN用户获取自己有权限的仓库列表
     */
    public function GetSvnUserRepList()
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
        parent::GetAuthz();

        /**
         * 对用户有权限的仓库路径列表进行一一验证
         * 
         * 确保该仓库的路径存在于仓库的最新版本库中
         */
        $this->SyncRepPathCheck();

        /**
         * 及时更新
         */
        parent::GetAuthz();

        /**
         * 用户有权限的仓库路径列表 => svn_user_pri_paths数据表
         * 
         * 1、列表中存在的但是数据表不存在则向数据表插入
         * 2、列表中不存在的但是数据表存在从数据表删除
         */
        $this->SyncUserRepAndDb();

        $pageSize = $this->payload['pageSize'];
        $currentPage = $this->payload['currentPage'];
        $searchKeyword = trim($this->payload['searchKeyword']);

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
                'rep_name'  => strtoupper($this->payload['sortType'])
            ],
            'svn_user_name' => $this->userName
        ]);

        $total = $this->database->count('svn_user_pri_paths', [
            'svnn_user_pri_path_id'
        ], [
            'AND' => [
                'OR' => [
                    'rep_name[~]' => $searchKeyword,
                ],
            ],
            'svn_user_name' => $this->userName
        ]);

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 修改仓库的备注信息
     */
    public function EditRepNote()
    {
        $this->database->update('svn_reps', [
            'rep_note' => $this->payload['rep_note']
        ], [
            'rep_name' => $this->payload['rep_name']
        ]);

        return message(200, 1, '已保存');
    }

    /**
     * SVN用户根据目录名称获取该目录下的文件和文件夹列表
     */
    public function GetUserRepCon()
    {
        $path = $this->payload['path'];

        $repName = $this->payload['rep_name'];

        /**
         * 获取svn检出地址
         * 
         * 目的为使用当前SVN用户的身份来进行被授权过的路径的内容浏览
         */
        $bindInfo = $this->Svn->GetSvnserveListen();
        $checkoutHost = 'svn://' . $bindInfo['bindHost'];
        if ($bindInfo['bindPort'] != '3690') {
            $checkoutHost = 'svn://' . $bindInfo['bindHost'] . ':' . $bindInfo['bindPort'];
        }

        /**
         * 获取SVN用户密码
         * 
         * 目的为使用该用户的权限进行操作 确保用户看到的就是所授权的
         */
        $svnUserPass = $this->SVNAdminUser->GetPassByUser($this->passwdContent, $this->userName);
        if ($svnUserPass == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        } else if ($svnUserPass == '1') {
            return message(200, 0, '用户不存在' . $this->userName);
        }

        /**
         * 使用svn list进行内容获取
         */
        $checkResult = $this->SVNAdminRep->CheckSvnUserPathAutzh($checkoutHost, $repName, $path, $this->userName, $svnUserPass);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }
        $result = $checkResult['data'];

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

        //按照文件夹在前、文件在后的顺序进行字典排序
        array_multisort(FunArrayColumn($data, 'resourceType'), SORT_DESC, $data);

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

        return message(200, 1, '', [
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
    public function GetRepCon()
    {
        /**
         * 有权限的开始路径
         * 
         * 管理员为 /
         * SVN用户为管理员设定的路径值
         */
        $path = $this->payload['path'];

        //获取全路径的一层目录树
        $cmdSvnlookTree = sprintf("'%s' tree  '%s' --full-paths --non-recursive '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path'] .  $this->payload['rep_name'], $path);
        $result = FunShellExec($cmdSvnlookTree);
        $result = $result['result'];
        $resultArray = explode("\n", trim($result));
        unset($resultArray[0]);
        $resultArray = array_values($resultArray);

        $data = [];
        foreach ($resultArray as $key => $value) {
            //获取文件或者文件夹最年轻的版本号
            $lastRev  = $this->SVNAdminRep->GetRepFileRev($this->payload['rep_name'], $value);

            //获取文件或者文件夹最年轻的版本的作者
            $lastRevAuthor = $this->SVNAdminRep->GetRepFileAuthor($this->payload['rep_name'], $lastRev);

            //同上 日期
            $lastRevDate = $this->SVNAdminRep->GetRepFileDate($this->payload['rep_name'], $lastRev);

            //同上 日志
            $lastRevLog = $this->SVNAdminRep->GetRepFileLog($this->payload['rep_name'], $lastRev);

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
                    'fileSize' => $this->SVNAdminRep->GetRepRevFileSize($this->payload['rep_name'], $value),
                    'revAuthor' => $lastRevAuthor,
                    'revNum' => 'r' . $lastRev,
                    'revTime' => $lastRevDate,
                    'revLog' => $lastRevLog,
                    'fullPath' => $value
                ]);
            }
        }

        //按照文件夹在前、文件在后的顺序进行字典排序
        array_multisort(FunArrayColumn($data, 'resourceType'), SORT_DESC, $data);

        //处理面包屑
        if ($path == '/') {
            $breadPathArray = ['/'];
            $breadNameArray = [$this->payload['rep_name']];
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
            $breadNameArray = [$this->payload['rep_name']];
            $tempPath = '/';
            for ($i = 1; $i < count($tempArray); $i += 2) {
                $tempPath .= $tempArray[$i] . $tempArray[$i + 1];
                array_push($breadPathArray, $tempPath);
                array_push($breadNameArray, $tempArray[$i]);
            }
        }

        return message(200, 1, '', [
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
    public function GetRepTree()
    {
        $path = $this->payload['path'];

        //获取全路径的一层目录树
        $cmdSvnlookTree = sprintf("'%s' tree  '%s' --full-paths --non-recursive '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path']  . $this->payload['rep_name'], $path);
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
            return message(200, 1, '', [
                [
                    'expand' => true,
                    'loading' => false,
                    'resourceType' => 2,
                    'title' => $this->payload['rep_name'] . '/',
                    'fullPath' => '/',
                    'children' => $data
                ]
            ]);
        } else {
            return message(200, 1, '', $data);
        }
    }

    /**
     * 获取某个仓库路径的用户权限列表
     */
    public function GetRepPathUserPri()
    {
        $result = $this->SVNAdminRep->GetRepUserListWithPri($this->authzContent, $this->payload['rep_name'], $this->payload['path']);
        if ($result == '0') {
            //没有该路径的记录
            if ($this->payload['path'] == '/') {
                //不正常 没有写入仓库记录
                return message(200, 0, '该仓库没有被写入配置文件！请刷新仓库列表以同步');
            } else {
                //正常 无记录
                return message(200, 1, '成功', []);
            }
        } else {
            foreach ($result as $key => $value) {
                $result[$key]['index'] = $key;
                if ($value['userPri'] == '') {
                    $result[$key]['userPri'] = 'no';
                }
            }
            return message(200, 1, '成功', $result);
        }
    }

    /**
     * 获取某个仓库路径的分组权限列表
     */
    public function GetRepPathGroupPri()
    {
        $result = $this->SVNAdminRep->GetRepGroupListWithPri($this->authzContent, $this->payload['rep_name'], $this->payload['path']);
        if ($result == '0') {
            //没有该路径的记录
            if ($this->payload['path'] == '/') {
                //不正常 没有写入仓库记录
                return message(200, 0, '该仓库没有被写入配置文件！请刷新仓库列表以同步');
            } else {
                //正常 无记录
                return message(200, 1, '成功', []);
            }
        } else {
            foreach ($result as $key => $value) {
                $result[$key]['index'] = $key;
                if ($value['groupPri'] == '') {
                    $result[$key]['groupPri'] = 'no';
                }
            }
            return message(200, 1, '成功', $result);
        }
    }

    /**
     * 增加某个仓库路径的用户权限
     */
    public function AddRepPathUserPri()
    {
        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $pri = $this->payload['pri'];
        $user = $this->payload['user'];

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
        $result = $this->SVNAdminRep->SetRepUserPri($this->authzContent, $user, $pri, $repName, $path);

        //没有该仓库路径记录
        if ($result == '0') {

            //没有该仓库路径记录 则进行插入
            $result = $this->SVNAdminRep->SetRepAuthz($this->authzContent, $repName, $path);

            if ($result == '1') {
                return message(200, 1, '未知错误');
            }

            //重新添加权限
            $result = $this->SVNAdminRep->SetRepUserPri($result, $user, $pri, $repName, $path);

            if ($result == '0') {
                return message(200, 1, '未知错误');
            }
        }

        //写入
        // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_authz_file']);

        FunFilePutContents($this->config_svn['svn_authz_file'], $result);

        //返回
        return message();
    }

    /**
     * 删除某个仓库路径的用户权限
     */
    public function DelRepPathUserPri()
    {
        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $user = $this->payload['user'];

        $result = $this->SVNAdminRep->DelRepUserPri($this->authzContent, $user, $repName, $path);

        if ($result == '0') {
            return message(200, 0, '不存在该仓库路径的记录');
        } else if ($result == '1') {
            return message(200, 0, '已被删除');
        } else {
            //写入
            // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_authz_file']);

            FunFilePutContents($this->config_svn['svn_authz_file'], $result);

            //返回
            return message();
        }
    }

    /**
     * 修改某个仓库路径的用户权限
     */
    public function EditRepPathUserPri()
    {
        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $pri = $this->payload['pri'];
        $user = $this->payload['user'];

        /**
         * 处理权限
         */
        $pri = $pri == 'no' ? '' : $pri;

        $result = $this->SVNAdminRep->UpdRepUserPri($this->authzContent, $user, $pri, $repName, $path);

        if ($result == '0') {
            return message(200, 0, '不存在该仓库路径的记录');
        }

        //写入
        // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_authz_file']);

        FunFilePutContents($this->config_svn['svn_authz_file'], $result);

        //返回
        return message();
    }

    /**
     * 增加某个仓库路径的分组权限
     */
    public function AddRepPathGroupPri()
    {
        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $pri = $this->payload['pri'];
        $group = $this->payload['group'];

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
        $result = $this->SVNAdminRep->SetRepGroupPri($this->authzContent, $group, $pri, $repName, $path);

        //没有该仓库路径记录
        if ($result == '0') {

            //没有该仓库路径记录 则进行插入
            $result = $this->SVNAdminRep->SetRepAuthz($this->authzContent, $repName, $path);

            if ($result == '1') {
                return message(200, 1, '未知错误');
            }

            //重新添加权限
            $result = $this->SVNAdminRep->SetRepGroupPri($result, $group, $pri, $repName, $path);

            if ($result == '0') {
                return message(200, 1, '未知错误');
            }
        }

        //写入
        // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_authz_file']);

        FunFilePutContents($this->config_svn['svn_authz_file'], $result);

        //返回
        return message();
    }

    /**
     * 删除某个仓库路径的分组权限
     */
    public function DelRepPathGroupPri()
    {
        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $group = $this->payload['group'];

        $result = $this->SVNAdminRep->DelRepGroupPri($this->authzContent, $group, $repName, $path);

        if ($result == '0') {
            return message(200, 0, '不存在该仓库路径的记录');
        } else if ($result == '1') {
            return message(200, 0, '已被删除');
        } else {
            //写入
            // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_authz_file']);

            FunFilePutContents($this->config_svn['svn_authz_file'], $result);

            //返回
            return message();
        }
    }

    /**
     * 修改某个仓库路径的分组权限
     */
    public function EditRepPathGroupPri()
    {
        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $pri = $this->payload['pri'];
        $group = $this->payload['group'];

        /**
         * 处理权限
         */
        $pri = $pri == 'no' ? '' : $pri;

        $result = $this->SVNAdminRep->UpdRepGroupPri($this->authzContent, $group, $pri, $repName, $path);

        if ($result == '0') {
            return message(200, 0, '不存在该仓库路径的记录');
        } else if ($result == '1') {
            return message(200, 0, '该仓库下不存在该分组');
        }

        //写入
        // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_authz_file']);

        FunFilePutContents($this->config_svn['svn_authz_file'], $result);

        //返回
        return message();
    }

    /**
     * 修改仓库名称
     */
    public function EditRepName()
    {
        //检查新仓库名是否合法
        $checkResult = $this->checkService->CheckRepName($this->payload['new_rep_name']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //检查原仓库是否不存在
        $checkResult = $this->SVNAdminRep->CheckRepCreate($this->payload['old_rep_name'], '要修改的仓库不存在');
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //检查新仓库名是否存在
        $checkResult = $this->SVNAdminRep->CheckRepExist($this->payload['new_rep_name'],  '已经存在同名仓库');
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //从仓库目录修改仓库名称
        FunShellExec('mv ' . $this->config_svn['rep_base_path'] .  $this->payload['old_rep_name'] . ' ' . $this->config_svn['rep_base_path'] . $this->payload['new_rep_name']);

        //检查修改过的仓库名称是否存在
        $checkResult = $this->SVNAdminRep->CheckRepCreate($this->payload['new_rep_name'], '修改仓库名称失败');
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //从数据库修改仓库名称
        $this->database->update('svn_reps', [
            'rep_name' => $this->payload['new_rep_name']
        ], [
            'rep_name' => $this->payload['old_rep_name']
        ]);

        //从配置文件修改仓库名称
        $this->SVNAdminRep->UpdRepAuthz($this->authzContent, $this->payload['old_rep_name'], $this->payload['new_rep_name']);

        //日志
        $this->Logs->InsertLog(
            '修改仓库名称',
            sprintf("原仓库名 %s 新仓库名 %s", $this->payload['old_rep_name'], $this->payload['new_rep_name']),
            $this->userName
        );

        return message();
    }

    /**
     * 删除仓库
     */
    public function DelRep()
    {
        //从配置文件删除指定仓库的所有路径
        $result = $this->SVNAdminRep->DelRepAuthz($this->authzContent, $this->payload['rep_name']);
        if ($result != '1') {
            // FunShellExec('echo \'' . $result . '\' > ' . $this->config_svn['svn_authz_file']);

            FunFilePutContents($this->config_svn['svn_authz_file'], $result);
        }

        //从数据库中删除
        $this->database->delete('svn_reps', [
            'rep_name' => $this->payload['rep_name']
        ]);

        //从仓库目录删除仓库文件夹
        FunShellExec('cd ' . $this->config_svn['rep_base_path'] . ' && rm -rf ./' . $this->payload['rep_name']);
        $checkResult = $this->SVNAdminRep->CheckRepDelete($this->payload['rep_name']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //日志
        $this->Logs->InsertLog(
            '删除仓库',
            sprintf("仓库名 %s", $this->payload['rep_name']),
            $this->userName
        );

        //返回
        return message();
    }

    /**
     * 获取仓库的属性内容（key-value的形式）
     */
    public function GetRepDetail()
    {
        $result = $this->SVNAdminRep->GetRepDetail($this->payload['rep_name']);
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
        return message(200, 1, '成功', $newArray);
    }

    /**
     * 获取备份文件夹下的文件列表
     */
    public function GetBackupList()
    {
        $result = FunGetDirFileList($this->config_svn['backup_base_path']);

        return message(200, 1, '成功', $result);
    }

    /**
     * 立即备份当前仓库
     */
    public function RepDump()
    {
        $this->SVNAdminRep->RepDump($this->payload['rep_name'], $this->payload['rep_name'] . '_' . date('YmdHis') . '_' . FunGetRandStr() . '.dump');

        return message();
    }

    /**
     * 删除备份文件
     */
    public function DelRepBackup()
    {
        $this->SVNAdminRep->DelRepBackup($this->payload['fileName']);

        return message();
    }

    /**
     * 下载备份文件
     */
    public function DownloadRepBackup()
    {
        $filePath = $this->config_svn['backup_base_path'] .  $this->payload['fileName'];
        $this->DownloadRepBackup2($filePath, $this->payload['fileName']);
    }

    /**
     * 下载备份文件
     */
    private function DownloadRepBackup1($filePath, $fileName)
    {
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
    private function DownloadRepBackup2($filePath, $fileName)
    {
        //文件类型
        $mimeType = 'application/octet-stream';

        //请求区域
        $range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;

        set_time_limit(0);

        $transfer = new Transfer($filePath, $mimeType, $range);

        $transfer->send();
    }

    /**
     * 获取上传限制
     */
    public function GetUploadLimit()
    {
        $file_uploads = ini_get('file_uploads');
        if ($file_uploads == 0 || $file_uploads == false || strtolower($file_uploads) == 'off') {
            $file_uploads = false;
        } else {
            $file_uploads = true;
        }

        // $webServer = strtolower($_SERVER['SERVER_SOFTWARE']);
        // if (strpos($webServer, 'nginx')) {
        //     $client_max_body_size = '';
        // }

        return message(200, 1, '成功', [
            'file_uploads' => $file_uploads,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ]);
    }

    /**
     * 上传文件到备份文件夹
     */
    public function UploadBackup()
    {
        $file_uploads = ini_get('file_uploads');
        if ($file_uploads == 0 || $file_uploads == false || strtolower($file_uploads) == 'off') {
            return message(200, 0, '文件上传功能关闭');
        }

        if (array_key_exists('file', $_FILES)) {
            //扩展名
            $fileType =  substr(strrchr($_FILES['file']['name'], '.'), 1);

            //文件名
            $fileName = $_FILES['file']['name'];

            //备份文件夹
            $localFilePath = $this->config_svn['backup_base_path'] .  $fileName;

            //保存
            $cmd = sprintf("mv '%s' '%s'", $_FILES['file']['tmp_name'], $localFilePath);
            FunShellExec($cmd);
            // move_uploaded_file($_FILES['file']['tmp_name'], $localFilePath);

            return message();
        } else {
            return message(200, 0, '参数不完整');
        }
    }

    /**
     * 从本地备份文件导入仓库
     */
    public function ImportRep()
    {
        //检查备份文件是否存在
        if (!file_exists($this->config_svn['backup_base_path'] .  $this->payload['fileName'])) {
            return message(200, 0, '备份文件不存在');
        }

        //检查操作的仓库是否存在
        $checkResult = $this->SVNAdminRep->CheckRepCreate($this->payload['rep_name'], '仓库不存在');
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //使用svndump
        $result = $this->SVNAdminRep->RepLoad($this->payload['rep_name'], $this->payload['fileName']);

        if ($result['error'] == '') {
            return message();
        } else {
            return message(200, 0, '导入错误', $result['error']);
        }
    }

    /**
     * 获取仓库的钩子和对应的内容列表
     */
    public function GetRepHooks()
    {
        //检查仓库是否存在
        $checkResult = $this->SVNAdminRep->CheckRepCreate($this->payload['rep_name'], '仓库不存在');
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        $repHooks =  [
            'start_commit' =>  [
                'fileName' => 'start-commit',
                'hasFile' => false,
                'con' => '',
                'tmpl' => ''
            ],
            'pre_commit' =>  [
                'fileName' => 'pre-commit',
                'hasFile' => false,
                'con' => '',
                'tmpl' => ''
            ],
            'post_commit' =>  [
                'fileName' => 'post-commit',
                'hasFile' => false,
                'con' => '',
                'tmpl' => ''
            ],
            'pre_lock' =>  [
                'fileName' => 'pre-lock',
                'hasFile' => false,
                'con' => '',
                'tmpl' => ''
            ],
            'post_lock' =>  [
                'fileName' => 'post-lock',
                'hasFile' => false,
                'con' => '',
                'tmpl' => ''
            ],
            'pre_unlock' =>  [
                'fileName' => 'pre-unlock',
                'hasFile' => false,
                'con' => '',
                'tmpl' => ''
            ],
            'post_unlock' =>  [
                'fileName' => 'post-unlock',
                'hasFile' => false,
                'con' => '',
                'tmpl' => ''
            ],
            'pre_revprop_change' =>  [
                'fileName' => 'pre-revprop-change',
                'hasFile' => false,
                'con' => '',
                'tmpl' => ''
            ],
            'post_revprop_change' =>  [
                'fileName' => 'post-revprop-change',
                'hasFile' => false,
                'con' => '',
                'tmpl' => ''
            ],
        ];

        $hooksPath = $this->config_svn['rep_base_path'] . $this->payload['rep_name'] . '/hooks/';

        if (!is_dir($hooksPath)) {
            return message(200, 0, '该仓库不存在hooks文件夹');
        }

        foreach ($repHooks as $key => $value) {
            if (file_exists($hooksPath . $value['fileName'])) {
                $repHooks[$key]['hasFile'] = true;
                $temp = FunShellExec(sprintf("cat '%s'", $hooksPath . $value['fileName']));
                $repHooks[$key]['con'] = $temp['result'];
            }
            if (file_exists($hooksPath . $value['fileName'] . '.tmpl')) {
                $temp = FunShellExec(sprintf("cat '%s'", $hooksPath . $value['fileName'] . '.tmpl'));
                $repHooks[$key]['tmpl'] = $temp['result'];
            }
        }

        return message(200, 1, '成功', $repHooks);
    }

    /**
     * 移除仓库钩子
     */
    public function DelRepHook()
    {
        //检查仓库是否存在
        $checkResult = $this->SVNAdminRep->CheckRepCreate($this->payload['rep_name'], '仓库不存在');
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        $hooksPath = $this->config_svn['rep_base_path'] . $this->payload['rep_name'] . '/hooks/';

        if (!is_dir($hooksPath)) {
            return message(200, 0, '该仓库不存在hooks文件夹');
        }

        if (!file_exists($hooksPath . $this->payload['fileName'])) {
            return message(200, 0, '已经移除该仓库钩子');
        }

        FunShellExec(sprintf("cd '%s' && rm -f ./'%s'", $hooksPath, $this->payload['fileName']));

        return message(200, 1, '移除成功');
    }

    /**
     * 修改仓库的某个钩子内容
     */
    public function EditRepHook()
    {
        $hooksPath = $this->config_svn['rep_base_path'] . $this->payload['rep_name'] . '/hooks/';

        //使用echo写入文件 当出现不规则的不成对的 ' " 等会出问题 当然也会包括其他问题
        FunFilePutContents($hooksPath . $this->payload['fileName'],$this->payload['content']);

        return message();
    }
}
