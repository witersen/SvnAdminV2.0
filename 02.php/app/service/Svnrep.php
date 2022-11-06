<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 18:12:08
 * @Description: QQ:1801168257
 */

namespace app\service;

// use Config;

class Svnrep extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    // private $Svngroup;
    private $Logs;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        // $this->Svngroup = new Svngroup();
        $this->Svn = new Svn();
        $this->Logs = new Logs();
    }

    /**
     * 新建仓库
     */
    public function CreateRep()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'rep_name' => ['type' => 'string', 'notNull' => true],
            'rep_note' => ['type' => 'string', 'notNull' => false],
            'rep_type' => ['type' => 'string', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $repBasePath = $this->configSvn['rep_base_path'];
        $repName = $this->payload['rep_name'];
        $repPath = $repBasePath . $repName;

        //检查仓库名是否合法
        $checkResult = $this->checkService->CheckRepName($repName);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //检查仓库是否存在
        clearstatcache();
        if (is_dir($repPath)) {
            return message(200, 0, '仓库已存在');
        }

        //创建空仓库
        //解决创建中文仓库乱码问题
        $cmd = sprintf("export LC_CTYPE=en_US.UTF-8 &&  '%s' create " . $repPath, $this->configBin['svnadmin']);
        funShellExec($cmd);

        //关闭selinux
        funShellExec('setenforce 0');

        if ($this->payload['rep_type'] == '2') {
            //以指定的目录结构初始化仓库
            $this->InitRepStruct($this->configSvn['templete_init_struct_01'], $repPath);
        }

        //检查是否创建成功
        if (!is_dir($repPath)) {
            return message(200, 0, '创建仓库失败');
        }

        //向authz写入仓库信息
        $result = $this->SVNAdmin->WriteRepPathToAuthz($this->authzContent, $repName, '/');
        if (is_numeric($result)) {
            if ($result == 851) {
                $result = $this->authzContent;
            } else {
                return message(200, 0, "同步到配置文件错误$result");
            }
        }
        funFilePutContents($this->configSvn['svn_authz_file'], $result);

        //写入数据库
        $this->database->delete('svn_reps', [
            'rep_name' =>  $repName,
        ]);
        $this->database->insert('svn_reps', [
            'rep_name' => $repName,
            'rep_size' => funGetDirSizeDu($repPath),
            'rep_note' => $this->payload['rep_note'],
            'rep_rev' => $this->GetRepRev($repName),
            'rep_uuid' => $this->GetRepUUID($repName)
        ]);

        //日志
        $this->Logs->InsertLog(
            '创建仓库',
            sprintf("仓库名:%s", $repName),
            $this->userName
        );

        return message();
    }

    /**
     * SVN仓库 => 数据库
     */
    private function SyncRepAndDb()
    {
        /**
         * 删除数据表重复插入的项
         */
        $dbRepList = $this->database->select('svn_reps', [
            'rep_id',
            'rep_name',
        ], [
            'GROUP' => [
                'rep_name'
            ]
        ]);
        $dbRepListAll = $this->database->select('svn_reps', [
            'rep_id',
            'rep_name',
        ]);

        $duplicates = array_diff(array_column($dbRepListAll, 'rep_id'), array_column($dbRepList, 'rep_id'));
        foreach ($duplicates as $value) {
            $this->database->delete('svn_reps', [
                'rep_id' => $value,
            ]);
        }

        /**
         * 数据对比增删改
         */
        $old = array_column($dbRepList, 'rep_name');
        $new = $this->GetSimpleRepList();

        //删除
        $delete = array_diff($old, $new);
        foreach ($delete as $value) {
            $this->database->delete('svn_reps', [
                'rep_name' => $value,
            ]);
        }

        //新增
        $create = array_diff($new, $old);
        foreach ($create as $value) {
            $this->database->insert('svn_reps', [
                'rep_name' => $value,
                'rep_size' => funGetDirSizeDu($this->configSvn['rep_base_path'] .  $value),
                'rep_note' => '',
                'rep_rev' => $this->GetRepRev($value),
                'rep_uuid' => $this->GetRepUUID($value)
            ]);
        }

        //更新
        $update = array_intersect($old, $new);
        foreach ($update as $value) {
            $this->database->update('svn_reps', [
                'rep_size' => funGetDirSizeDu($this->configSvn['rep_base_path'] .  $value),
                'rep_rev' => $this->GetRepRev($value),
                'rep_uuid' => $this->GetRepUUID($value)
            ], [
                'rep_name' => $value
            ]);
        }

        return message();
    }

    /**
     * SVN仓库 => authz文件
     */
    private function SyncRepAndAuthz()
    {
        $svnRepList = $this->GetSimpleRepList();

        $svnRepAuthzList = $this->SVNAdmin->GetRepListFromAuthz($this->authzContent);

        $authzContet = $this->authzContent;

        foreach ($svnRepList as $key => $value) {
            if (!in_array($value, $svnRepAuthzList)) {
                $result = $this->SVNAdmin->WriteRepPathToAuthz($authzContet, $value, '/');
                if (is_numeric($result)) {
                    if ($result == 851) {
                    } else {
                        json1(200, 0, "同步到配置文件错误$authzContet");
                    }
                } else {
                    $authzContet = $result;
                }
            }
        }

        foreach ($svnRepAuthzList as $key => $value) {
            if (!in_array($value, $svnRepList)) {
                $result = $this->SVNAdmin->DelRepFromAuthz($authzContet, $value);
                if (is_numeric($result)) {
                    if ($result == 751) {
                    } else {
                        json1(200, 0, "同步到配置文件错误$authzContet");
                    }
                } else {
                    $authzContet = $result;
                }
            }
        }

        if ($authzContet != $this->authzContent) {
            funFilePutContents($this->configSvn['svn_authz_file'], $authzContet);
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
        $authzContent = $this->authzContent;

        //获取用户有权限的仓库路径列表
        $userRepList = $this->SVNAdmin->GetUserAllPri($this->authzContent, $this->userName);
        if (is_numeric($userRepList)) {
            if ($userRepList == 612) {
                json1(200, 0, '文件格式错误(不存在[groups]标识)');
            } else if ($userRepList == 700) {
                json1(200, 0, '对象不存在');
            } else if ($userRepList == 901) {
                json1(200, 0, '不支持的授权对象类型');
            } else {
                json1(200, 0, "错误码$userRepList");
            }
        }

        foreach ($userRepList as $key => $value) {
            $cmd = sprintf("'%s' tree  '%s' --full-paths --non-recursive '%s'", $this->configBin['svnlook'], $this->configSvn['rep_base_path'] .  $value['repName'], $value['priPath']);
            $result = funShellExec($cmd);

            if (strstr($result['error'], 'svnlook: E160013:')) {
                //路径在仓库不存在
                //从配置文件删除指定仓库的指定路径
                $tempResult = $this->SVNAdmin->DelRepPathFromAuthz($authzContent, $value['repName'], $value['priPath']);
                if (!is_numeric($tempResult)) {
                    $authzContent = $tempResult;
                }
            }
        }

        //写入配置文件
        if ($authzContent != $this->authzContent) {
            funFilePutContents($this->configSvn['svn_authz_file'], $authzContent);
        }
    }

    /**
     * 用户有权限的仓库路径列表 => 数据库
     */
    private function SyncUserRepAndDb()
    {
        //获取用户有权限的仓库路径列表
        $userRepList = $this->SVNAdmin->GetUserAllPri($this->authzContent, $this->userName);
        if (is_numeric($userRepList)) {
            if ($userRepList == 612) {
                json1(200, 0, '文件格式错误(不存在[groups]标识)');
            } else if ($userRepList == 700) {
                json1(200, 0, '对象不存在');
            } else if ($userRepList == 901) {
                json1(200, 0, '不支持的授权对象类型');
            } else {
                json1(200, 0, "错误码$userRepList");
            }
        }
        $new = [];
        $newRepList = [];
        foreach ($userRepList as $value) {
            $unique = $value['repName'] . $value['priPath'] . $value['repPri'];
            $new[] = $unique;
            $newRepList[$unique] = $value;
        }

        $userRepList = $this->database->select('svn_user_pri_paths', [
            'svnn_user_pri_path_id [Int]',
            'rep_name',
            'pri_path',
            'rep_pri',
        ], [
            'svn_user_name' => $this->userName
        ]);
        $old = [];
        $oldRepList = [];
        foreach ($userRepList as $value) {
            $unique = $value['rep_name'] . $value['pri_path'] . $value['rep_pri'];
            $old[] = $unique;
            $oldRepList[$unique] = $value;
        }
        unset($userRepList);

        $delete = array_diff($old, $new);
        foreach ($delete as $value) {
            $this->database->delete('svn_user_pri_paths', [
                'svnn_user_pri_path_id' => $oldRepList[$value]['svnn_user_pri_path_id'],
            ]);
        }

        $create = array_diff($new, $old);
        foreach ($create as $value) {
            $this->database->insert('svn_user_pri_paths', [
                'rep_name' => $newRepList[$value]['repName'],
                'pri_path' => $newRepList[$value]['priPath'],
                'rep_pri' => $newRepList[$value]['repPri'],
                'svn_user_name' => $this->userName,
                'unique' => '', //兼容2.3.3及之前版本 从2.3.3.1版本开始无实际意义
                'second_pri' => 0
            ]);
        }
    }

    /**
     * 获取仓库列表
     */
    public function GetRepList()
    {
        $sync = $this->payload['sync'];
        $page = $this->payload['page'];

        if ($sync) {
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
            $syncResult = $this->SyncRepAndDb();
            if ($syncResult['status'] != 1) {
                return message($syncResult['code'], $syncResult['status'], $syncResult['message'], $syncResult['data']);
            }
        }

        if ($page) {
            $pageSize = $this->payload['pageSize'];
            $currentPage = $this->payload['currentPage'];
        }
        $searchKeyword = trim($this->payload['searchKeyword']);

        //分页
        if ($page) {
            $begin = $pageSize * ($currentPage - 1);
        }

        /**
         * 特殊字符转义处理 todo
         */
        // $configDatabase = Config::get('database');
        // if ($configDatabase['database_type'] == 'mysql') {
        //     $searchKeyword = str_replace([
        //         '_',
        //     ], [
        //         '[_]',
        //     ], $searchKeyword);
        // } else if ($configDatabase['database_type'] == 'sqlite') {
        //     $searchKeyword = str_replace([
        //         '/', "'", '[', ']', '%', '&', '_', '(', ')'
        //     ], [
        //         '//', "''", '/[', '/]', '/%', '/&', '/_', '/(', '/)'
        //     ], $searchKeyword);
        // }

        if ($page) {
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
        } else {
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
                'ORDER' => [
                    $this->payload['sortName']  => strtoupper($this->payload['sortType'])
                ]
            ]);
        }

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
            $list[$key]['rep_size'] = funFormatSize($value['rep_size']);
        }

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * SVN用户获取有权限的仓库路径列表
     */
    public function GetSvnUserRepList()
    {
        $sync = $this->payload['sync'];
        $page = $this->payload['page'];

        if ($sync) {
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
            $this->authzContent = file_get_contents($this->configSvn['svn_authz_file']);

            /**
             * 对用户有权限的仓库路径列表进行一一验证
             * 
             * 确保该仓库的路径存在于仓库的最新版本库中
             * 
             * 暂时去除 这样做可能会对已经配置的路径造成误删除 因为文件或者文件夹可能为误删除 进行此同步后就会造成整个路径误删除
             */
            // $this->SyncRepPathCheck();

            /**
             * 及时更新
             */
            // $this->authzContent = file_get_contents($this->configSvn['svn_authz_file']);

            /**
             * 用户有权限的仓库路径列表 => svn_user_pri_paths数据表
             * 
             * 1、列表中存在的但是数据表不存在则向数据表插入
             * 2、列表中不存在的但是数据表存在从数据表删除
             */
            $this->SyncUserRepAndDb();
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
            $list = $this->database->select('svn_user_pri_paths', [
                'svnn_user_pri_path_id [Int]',
                'rep_name',
                'pri_path',
                'rep_pri',
                'second_pri [Int]'
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
        } else {
            $list = $this->database->select('svn_user_pri_paths', [
                'svnn_user_pri_path_id [Int]',
                'rep_name',
                'pri_path',
                'rep_pri',
                'second_pri [Int]'
            ], [
                'AND' => [
                    'OR' => [
                        'rep_name[~]' => $searchKeyword,
                    ],
                ],
                'ORDER' => [
                    'rep_name'  => strtoupper($this->payload['sortType'])
                ],
                'svn_user_name' => $this->userName
            ]);
        }

        foreach ($list as $key => $value) {
            $list[$key]['second_pri'] = $value['second_pri'] == 1 ? true : false;
        }

        $total = $this->database->count('svn_user_pri_paths', [
            'svnn_user_pri_path_id [Int]'
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
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'path' => ['type' => 'string', 'notNull' => true],
            'rep_name' => ['type' => 'string', 'notNull' => false],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        //检查仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $this->payload['rep_name'])) {
            return message(200, 0, '仓库不存在');
        }

        $path = $this->payload['path'];
        $repName = $this->payload['rep_name'];

        $result = $this->GetSvnList($path, $repName);
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message']);
        }

        $result = $result['data'];

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
                if (substr($result, -1) != '/') {
                    $tempArray = explode('/', $path);
                    //确定为单文件授权
                    $isSingleFilePri = ($result == end($tempArray)) ? true : false;
                }
            }
        }

        /**
         * 获取版本号等文件详细信息
         * 
         * 此处也要针对单文件授权进行单独处理
         */
        $data = [];
        foreach ($resultArray as $key => $value) {
            //补全路径
            $value = $isSingleFilePri ? $path : rtrim($path, '/') . '/' . $value;

            //获取文件或者文件夹最年轻的版本号
            $lastRev  = $this->GetRepFileRev($repName, $value);

            //获取文件或者文件夹最年轻的版本的作者
            $lastRevAuthor = $this->GetRepFileAuthor($repName, $lastRev);

            //同上 日期
            $lastRevDate = $this->GetRepFileDate($repName, $lastRev);

            //同上 日志
            $lastRevLog = $this->GetRepFileLog($repName, $lastRev);

            $pathArray = explode('/', $value);
            $pathArray = array_values(array_filter($pathArray, 'funArrayValueFilter'));
            if (substr($value, -1) == '/') {
                array_push($data, [
                    'resourceType' => 2,
                    'resourceName' => end($pathArray),
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
                    'resourceName' => end($pathArray),
                    'fileSize' => $this->GetRepRevFileSize($repName, $value),
                    'revAuthor' => $lastRevAuthor,
                    'revNum' => 'r' . $lastRev,
                    'revTime' => $lastRevDate,
                    'revLog' => $lastRevLog,
                    'fullPath' => $value
                ]);
            }
        }

        //按照文件夹在前、文件在后的顺序进行字典排序
        array_multisort(array_column($data, 'resourceType'), SORT_DESC, $data);

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
        //检查仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $this->payload['rep_name'])) {
            return message(200, 0, '仓库不存在');
        }

        /**
         * 有权限的开始路径
         * 
         * 管理员为 /
         * SVN用户为管理员设定的路径值
         */
        $path = $this->payload['path'];

        //获取全路径的一层目录树
        $cmdSvnlookTree = sprintf("'%s' tree  '%s' --full-paths --non-recursive '%s'", $this->configBin['svnlook'], $this->configSvn['rep_base_path'] .  $this->payload['rep_name'], $path);
        $result = funShellExec($cmdSvnlookTree);
        if ($result['code'] != 0) {
            return ['code' => 200, 'status' => 0, 'message' => $result['error'], 'data' => []];
        }
        $result = $result['result'];
        $resultArray = explode("\n", trim($result));
        unset($resultArray[0]);
        $resultArray = array_values($resultArray);

        $data = [];
        foreach ($resultArray as $key => $value) {
            //获取文件或者文件夹最年轻的版本号
            $lastRev  = $this->GetRepFileRev($this->payload['rep_name'], $value);

            //获取文件或者文件夹最年轻的版本的作者
            $lastRevAuthor = $this->GetRepFileAuthor($this->payload['rep_name'], $lastRev);

            //同上 日期
            $lastRevDate = $this->GetRepFileDate($this->payload['rep_name'], $lastRev);

            //同上 日志
            $lastRevLog = $this->GetRepFileLog($this->payload['rep_name'], $lastRev);

            $pathArray = explode('/', $value);
            $pathArray = array_values(array_filter($pathArray, 'funArrayValueFilter'));
            if (substr($value, -1) == '/') {
                array_push($data, [
                    'resourceType' => 2,
                    'resourceName' => end($pathArray),
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
                    'resourceName' => end($pathArray),
                    'fileSize' => $this->GetRepRevFileSize($this->payload['rep_name'], $value),
                    'revAuthor' => $lastRevAuthor,
                    'revNum' => 'r' . $lastRev,
                    'revTime' => $lastRevDate,
                    'revLog' => $lastRevLog,
                    'fullPath' => $value
                ]);
            }
        }

        //按照文件夹在前、文件在后的顺序进行字典排序
        array_multisort(array_column($data, 'resourceType'), SORT_DESC, $data);

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
     * SVN用户配置目录授权用
     */
    public function GetRepTree()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'path' => ['type' => 'string', 'notNull' => true],
            'rep_name' => ['type' => 'string', 'notNull' => false],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $path = $this->payload['path'];
        $repName = $this->payload['rep_name'];

        //检查仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $repName)) {
            return message(200, 0, '仓库不存在');
        }

        $resultArray = [];
        if ($this->userRoleId == 1) {
            //获取全路径的一层目录树
            $cmdSvnlookTree = sprintf("'%s' tree  '%s' --full-paths --non-recursive '%s'", $this->configBin['svnlook'], $this->configSvn['rep_base_path']  . $repName, $path);
            $result = funShellExec($cmdSvnlookTree);
            if ($result['code'] != 0) {
                return message(200, 0, $result['error']);
            }
            $result = $result['result'];

            $resultArray = explode("\n", trim($result));
            unset($resultArray[0]);
            $resultArray = array_values($resultArray);
        } else if ($this->userRoleId == 2) {
            $result = $this->GetSvnList($path, $repName);
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message']);
            }
            $result = $result['data'];

            /**
             * 判断结果是否为空
             * 判断其他的意外情况
             */
            if ($result == '') {
                $resultArray = [];
            } else {
                $resultArray = explode("\n", $result);
            }
        }

        $data = [];
        foreach ($resultArray as $value) {
            $pathArray = explode('/', $value);
            $pathArray = array_values(array_filter($pathArray, 'funArrayValueFilter'));
            if (substr($value, -1) == '/') {
                array_push($data, [
                    'expand' => false,
                    'loading' => false,
                    'resourceType' => 2,
                    'title' => end($pathArray) . '/',
                    'fullPath' => $value,
                    'children' => []
                ]);
            } else {
                array_push($data, [
                    'resourceType' => 1,
                    'title' => end($pathArray),
                    'fullPath' => $value,
                ]);
            }
        }

        //按照文件夹在前、文件在后的顺序进行字典排序
        array_multisort(array_column($data, 'resourceType'), SORT_DESC, $data);

        if ($path == '/') {
            return message(200, 1, '', [
                [
                    'expand' => true,
                    'loading' => false,
                    'resourceType' => 2,
                    'title' => $repName . '/',
                    'fullPath' => '/',
                    'children' => $data
                ]
            ]);
        } else {
            return message(200, 1, '', $data);
        }
    }

    /**
     * 获取某个仓库路径的所有权限列表
     * 
     * 管理员
     * SVN用户
     */
    public function GetRepPathAllPri()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'path' => ['type' => 'string', 'notNull' => true],
            'rep_name' => ['type' => 'string', 'notNull' => true],
            'svnn_user_pri_path_id' => ['type' => 'integer', 'required' => $this->userRoleId == 2]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        //检查仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $this->payload['rep_name'])) {
            return message(200, 0, '仓库不存在');
        }

        $result = $this->SVNAdmin->GetRepPathPri($this->authzContent, $this->payload['rep_name'], $this->payload['path']);
        if (is_numeric($result)) {
            if ($result == 751) {
                //没有该路径的记录
                if ($this->payload['path'] == '/') {
                    //不正常 没有写入仓库记录
                    return message(200, 0, '该仓库没有被写入配置文件！请刷新仓库列表以同步');
                } else {
                    //正常 无记录
                    return message(200, 1, '成功', []);
                }
            } else {
                return message(200, 0, "错误码$result");
            }
        } else {
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
                        'objectType' => $value['objectType'],
                        'objectName' => $value['objectName']
                    ], $filters)) {
                        unset($result[$key]);
                    }
                }
            }
            return message(200, 1, '成功', $result);
        }
    }

    /**
     * 为某仓库路径下增加权限
     *
     * @return array
     */
    public function AddRepPathPri()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'path' => ['type' => 'string', 'notNull' => true],
            'rep_name' => ['type' => 'string', 'notNull' => true],
            'objectType' => ['type' => 'string', 'notNull' => true],
            'objectPri' => ['type' => 'string', 'notNull' => true],
            'objectName' => ['type' => 'string', 'notNull' => true],
            'svnn_user_pri_path_id' => ['type' => 'integer', 'required' => $this->userRoleId == 2]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $objectType = $this->payload['objectType'];
        $objectPri = $this->payload['objectPri'];
        $objectName = $this->payload['objectName'];

        //检查仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $repName)) {
            return message(200, 0, '仓库不存在');
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
            if (!in_array([
                'objectType' => $objectType,
                'objectName' => $objectName
            ], $filters)) {
                return message(200, 0, '无权限的操作对象');
            }

            //不可操作自身
            if ($objectType == 'user' && $objectName == $this->userName) {
                return message(200, 0, '不可操作自身');
            }

            //校验路径是否有权 todo
        }

        /**
         * 处理权限
         */
        $objectPri = $objectPri == 'no' ? '' : $objectPri;

        $result = $this->SVNAdmin->AddRepPathPri($this->authzContent, $repName, $path, $objectType, false, $objectName, $objectPri);

        if (is_numeric($result)) {
            if ($result == 751) {
                //没有该仓库路径记录 则进行插入
                $result = $this->SVNAdmin->WriteRepPathToAuthz($this->authzContent, $repName, $path);
                if (is_numeric($result)) {
                    if ($result == 851) {
                        $result = $this->authzContent;
                    } else {
                        return message(200, 0, "错误码$result");
                    }
                } else {
                    //重新写入权限
                    $result = $this->SVNAdmin->AddRepPathPri($result, $repName, $path, $objectType, false, $objectName, $objectPri);
                    if (is_numeric($result)) {
                        return message(200, 0, "错误码$result");
                    }
                }
            } else if ($result == 801) {
                return message(200, 0, '对象已有授权记录');
            } else if ($result == 901) {
                return message(200, 0, '不支持的授权对象类型');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        //写入
        funFilePutContents($this->configSvn['svn_authz_file'], $result);

        //返回
        return message();
    }

    /**
     * 修改某个仓库路径下的权限
     */
    public function EditRepPathPri()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'path' => ['type' => 'string', 'notNull' => true],
            'rep_name' => ['type' => 'string', 'notNull' => true],
            'objectType' => ['type' => 'string', 'notNull' => true],
            'objectPri' => ['type' => 'string', 'notNull' => true],
            'objectName' => ['type' => 'string', 'notNull' => true],
            'invert' => ['type' => 'boolean'],
            'svnn_user_pri_path_id' => ['type' => 'integer', 'required' => $this->userRoleId == 2]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $objectType = $this->payload['objectType'];
        $invert = $this->payload['invert'];
        $objectName = $this->payload['objectName'];
        $objectPri = $this->payload['objectPri'];

        //检查仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $repName)) {
            return message(200, 0, '仓库不存在');
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
            if (!in_array([
                'objectType' => $objectType,
                'objectName' => $objectName
            ], $filters)) {
                return message(200, 0, '无权限的操作对象');
            }

            //不可操作自身
            if ($objectType == 'user' && $objectName == $this->userName) {
                return message(200, 0, '不可操作自身');
            }

            //校验路径是否有权 todo
        }

        /**
         * 处理权限
         */
        $objectPri = $objectPri == 'no' ? '' : $objectPri;

        $result = $this->SVNAdmin->EditRepPathPri($this->authzContent, $repName, $path, $objectType, $invert == 1 ? true : false, $objectName, $objectPri);

        if (is_numeric($result)) {
            if ($result == 751) {
                return message(200, 0, '不存在该仓库路径');
            } else if ($result == 901) {
                return message(200, 0, '不支持的授权对象类型');
            } else if ($result == 701) {
                return message(200, 0, '仓库路径下不存在该对象的权限记录');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        //写入
        funFilePutContents($this->configSvn['svn_authz_file'], $result);

        //返回
        return message();
    }

    /**
     * 删除某个仓库下的权限
     */
    public function DelRepPathPri()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'path' => ['type' => 'string', 'notNull' => true],
            'rep_name' => ['type' => 'string', 'notNull' => true],
            'objectType' => ['type' => 'string', 'notNull' => true],
            'objectName' => ['type' => 'string', 'notNull' => true],
            'svnn_user_pri_path_id' => ['type' => 'integer', 'required' => $this->userRoleId == 2]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $objectType = $this->payload['objectType'];
        $objectName = $this->payload['objectName'];

        //检查仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $repName)) {
            return message(200, 0, '仓库不存在');
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
            if (!in_array([
                'objectType' => $objectType,
                'objectName' => $objectName
            ], $filters)) {
                return message(200, 0, '无权限的操作对象');
            }

            //不可操作自身
            if ($objectType == 'user' && $objectName == $this->userName) {
                return message(200, 0, '不可操作自身');
            }

            //校验路径是否有权 todo
        }

        $result = $this->SVNAdmin->DelRepPathPri($this->authzContent, $repName, $path, $objectType, $objectName);

        if (is_numeric($result)) {
            if ($result == 751) {
                return message(200, 0, '不存在该仓库路径的记录');
            } else if ($result == 901) {
                return message(200, 0, '不支持的授权对象类型');
            } else if ($result == 701) {
                return message(200, 0, '已删除');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        //写入
        funFilePutContents($this->configSvn['svn_authz_file'], $result);

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
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $this->payload['old_rep_name'])) {
            return message(200, 0, '要修改的仓库不存在');
        }

        //检查新仓库名是否存在
        if (is_dir($this->configSvn['rep_base_path'] . $this->payload['new_rep_name'])) {
            return message(200, 0, '已经存在同名仓库');
        }

        //从仓库目录修改仓库名称
        funShellExec('mv ' . $this->configSvn['rep_base_path'] .  $this->payload['old_rep_name'] . ' ' . $this->configSvn['rep_base_path'] . $this->payload['new_rep_name']);

        //检查修改过的仓库名称是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $this->payload['new_rep_name'])) {
            return message(200, 0, '修改仓库名称失败');
        }

        //从数据库修改仓库名称
        $this->database->update('svn_reps', [
            'rep_name' => $this->payload['new_rep_name']
        ], [
            'rep_name' => $this->payload['old_rep_name']
        ]);

        //从配置文件修改仓库名称
        $result = $this->SVNAdmin->UpdRepFromAuthz($this->authzContent, $this->payload['old_rep_name'], $this->payload['new_rep_name']);
        if (is_numeric($result)) {
            if ($result == 751) {
                return message(200, 0, '仓库不存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        funFilePutContents($this->configSvn['svn_authz_file'], $result);

        //日志
        $this->Logs->InsertLog(
            '修改仓库名称',
            sprintf("原仓库名:%s 新仓库名:%s", $this->payload['old_rep_name'], $this->payload['new_rep_name']),
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
        $authzContet = $this->SVNAdmin->DelRepFromAuthz($this->authzContent, $this->payload['rep_name']);
        if (is_numeric($authzContet)) {
            if ($authzContet == 751) {
            } else {
                return message(200, 0, "错误码$authzContet");
            }
        } else {
            funFilePutContents($this->configSvn['svn_authz_file'], $authzContet);
        }

        //从数据库中删除
        $this->database->delete('svn_reps', [
            'rep_name' => $this->payload['rep_name']
        ]);

        //从仓库目录删除仓库文件夹
        funShellExec('cd ' . $this->configSvn['rep_base_path'] . ' && rm -rf ./' . $this->payload['rep_name']);
        clearstatcache();
        if (is_dir($this->configSvn['rep_base_path'] .  $this->payload['rep_name'])) {
            return message(200, 0, '删除失败');
        }

        //日志
        $this->Logs->InsertLog(
            '删除仓库',
            sprintf("仓库名:%s", $this->payload['rep_name']),
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
        //检查仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $this->payload['rep_name'])) {
            return message(200, 0, '仓库不存在');
        }

        $result = $this->GetRepDetail110($this->payload['rep_name']);
        if ($result['code'] == 0) {
            $result = $result['result'];
            //Subversion 1.10 及以上版本
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
        } else {
            //Subversion 1.8 -1.9
            $newArray = [
                [
                    'repKey' => 'Path',
                    'repValue' => $this->configSvn['rep_base_path'] .  $this->payload['rep_name'],
                ],
                [
                    'repKey' => 'UUID',
                    'repValue' => $this->database->get('svn_reps', 'rep_uuid', [
                        'rep_name' => $this->payload['rep_name']
                    ])
                ],
            ];
        }

        return message(200, 1, '成功', $newArray);
    }

    /**
     * 重设仓库的UUID
     */
    public function SetUUID()
    {
        if ($this->payload['uuid'] == '') {
            $cmd = sprintf("'%s' setuuid '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $this->payload['rep_name']);
        } else {
            $cmd = sprintf("'%s' setuuid '%s' '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $this->payload['rep_name'], $this->payload['uuid']);
        }

        $result = funShellExec($cmd);

        if ($result['code'] == 0) {
            $this->database->update('svn_reps', [
                'rep_uuid' => $this->GetRepUUID($this->payload['rep_name'])
            ], [
                'rep_name' => $this->payload['rep_name']
            ]);

            return message();
        } else {
            return message(200, 0, $result['error']);
        }
    }

    /**
     * 获取备份文件夹下的文件列表
     */
    public function GetBackupList()
    {
        $result = funGetDirFileList($this->configSvn['backup_base_path']);

        foreach ($result as $key => $value) {
            $result[$key]['fileToken'] = hash_hmac('md5', $value['fileName'], $this->configSign['signature']);
            $result[$key]['fileUrl'] = sprintf('/api.php?c=Svnrep&a=DownloadRepBackup&t=web&fileName=%s&token=%s', $value['fileName'], $result[$key]['fileToken']);
        }

        return message(200, 1, '成功', $result);
    }

    /**
     * 立即备份当前仓库
     */
    public function RepDump()
    {
        //检查仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $this->payload['rep_name'])) {
            return message(200, 0, '仓库不存在');
        }

        $backupName = $this->payload['rep_name'] . '_' . date('YmdHis') . '_' . uniqid() . funGetRandStr() . '.dump';

        //使用 svnadmin dump 备份仓库
        $cmd = sprintf("'%s' dump '%s' --quiet  > '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $this->payload['rep_name'], $this->configSvn['backup_base_path'] .  $backupName);
        $result = funShellExec($cmd);

        if ($result['code'] != 0) {
            return message(200, 0, $result['error'], []);
        }

        return message();
    }

    /**
     * 删除备份文件
     */
    public function DelRepBackup()
    {
        $cmd = sprintf("cd '%s' && rm -f './%s'", $this->configSvn['backup_base_path'], $this->payload['fileName']);
        funShellExec($cmd);

        return message();
    }

    /**
     * 下载前请求文件名和文件token
     */
    public function GetDownloadInfo()
    {
    }

    /**
     * 下载备份文件
     */
    public function DownloadRepBackup()
    {
        if (empty($_GET['fileName'])) {
            json1(200, 0, '缺少文件名');
        }
        $fileName = $_GET['fileName'];
        $filePath = $this->configSvn['backup_base_path'] .  $fileName;

        if (empty($_GET['token'])) {
            json1(200, 0, '缺少文件token');
        }
        $token = $_GET['token'];

        if ($token !== hash_hmac('md5', $fileName, $this->configSign['signature'])) {
            json1(200, 0, '文件token无效');
        }

        if (!file_exists($this->configSvn['backup_base_path'] .  $fileName)) {
            json1(200, 0, '文件不存在');
        }

        $fp = @fopen($filePath, 'rb');
        if ($fp) {
            $file_size = filesize($filePath);

            header('content-type:application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $fileName);

            header('HTTP/1.1 200 OK');
            header('Accept-Ranges:bytes');
            header('content-length:' . $file_size);

            ob_end_clean();
            ob_start();

            while (!feof($fp)) {
                echo fread($fp, 4096);
                ob_flush();
                flush();
            }

            ob_end_flush();

            fclose($fp);
        }
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
            $localFilePath = $this->configSvn['backup_base_path'] .  $fileName;

            //保存
            $cmd = sprintf("mv '%s' '%s'", $_FILES['file']['tmp_name'], $localFilePath);
            funShellExec($cmd);
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
        if (!file_exists($this->configSvn['backup_base_path'] .  $this->payload['fileName'])) {
            return message(200, 0, '备份文件不存在');
        }

        //检查操作的仓库是否存在
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $this->payload['rep_name'])) {
            return message(200, 0, '仓库不存在');
        }

        //使用 svnadmin load 导入仓库
        $cmd = sprintf("'%s' load --quiet '%s' < '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $this->payload['rep_name'], $this->configSvn['backup_base_path'] .  $this->payload['fileName']);
        $result = funShellExec($cmd);

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
        clearstatcache();
        if (!is_dir($this->configSvn['rep_base_path'] . $this->payload['rep_name'])) {
            return message(200, 0, '仓库不存在');
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

        $hooksPath = $this->configSvn['rep_base_path'] . $this->payload['rep_name'] . '/hooks/';

        clearstatcache();
        if (!is_dir($hooksPath)) {
            return message(200, 0, '该仓库不存在hooks文件夹');
        }

        foreach ($repHooks as $key => $value) {
            $hookFile = $hooksPath . $value['fileName'];
            $hookTmpleFile = $hookFile . '.tmpl';

            if (file_exists($hookFile)) {
                $repHooks[$key]['hasFile'] = true;

                if (!is_readable($hookFile)) {
                    return message(200, 0, '文件' . $hookFile . '不可读');
                }
                $repHooks[$key]['con'] = file_get_contents($hookFile);
            }
            if (file_exists($hookTmpleFile)) {

                if (!is_readable($hookTmpleFile)) {
                    return message(200, 0, '文件' . $hookTmpleFile . '不可读');
                }
                $repHooks[$key]['tmpl'] = file_get_contents($hookTmpleFile);
            }
        }

        return message(200, 1, '成功', $repHooks);
    }

    /**
     * 移除仓库钩子
     */
    public function DelRepHook()
    {
        clearstatcache();

        $hooksPath = $this->configSvn['rep_base_path'] . $this->payload['rep_name'] . '/hooks/';

        if (is_dir($hooksPath)) {
            if (!file_exists($hooksPath . $this->payload['fileName'])) {
                return message(200, 0, '已经移除该仓库钩子');
            }
        } else {
            return message(200, 0, '仓库不存在');
        }

        funShellExec(sprintf("cd '%s' && rm -f ./'%s'", $hooksPath, $this->payload['fileName']));

        return message(200, 1, '移除成功');
    }

    /**
     * 修改仓库的某个钩子内容
     */
    public function EditRepHook()
    {
        $hooksPath = $this->configSvn['rep_base_path'] . $this->payload['rep_name'] . '/hooks/';

        //使用echo写入文件 当出现不规则的不成对的 ' " 等会出问题 当然也会包括其他问题
        funFilePutContents($hooksPath . $this->payload['fileName'], $this->payload['content']);

        funShellExec('chmod 777 -R ' . $this->configSvn['home_path']);

        return message();
    }

    /**
     * 获取常用钩子列表
     */
    public function GetRecommendHooks()
    {
        clearstatcache();

        $list = [];

        $recommend_hook_path = $this->configSvn['recommend_hook_path'];
        clearstatcache();
        if (!is_dir($recommend_hook_path)) {
            return message(200, 0, '未创建自定义钩子目录');
        }

        if (!is_readable($recommend_hook_path)) {
            return message(200, 0, '目录' . $recommend_hook_path . '不可读');
        }
        $dirs = scandir($recommend_hook_path);

        foreach ($dirs as $dir) {
            clearstatcache();

            if ($dir == '.' || $dir == '..') {
                continue;
            }

            if (!is_dir($recommend_hook_path . $dir)) {
                continue;
            }

            if (!is_readable($recommend_hook_path . $dir)) {
                return message(200, 0, '目录' . $recommend_hook_path . $dir . '不可读');
            }

            $dirFiles = scandir($recommend_hook_path . $dir);

            if (!in_array('hookDescription', $dirFiles) || !in_array('hookName', $dirFiles)) {
                continue;
            }

            if (!is_readable($recommend_hook_path . $dir . '/hookName')) {
                return message(200, 0, '文件' . $recommend_hook_path . $dir . '/hookName' . '不可读');
            }
            $hookName = file_get_contents($recommend_hook_path . $dir . '/hookName');

            if (!file_exists($recommend_hook_path . $dir . '/' . trim($hookName))) {
                continue;
            }

            if (!is_readable($recommend_hook_path . $dir . '/' . $hookName)) {
                return message(200, 0, '文件' . $recommend_hook_path . $dir . '/' . $hookName . '不可读');
            }
            $hookContent = file_get_contents($recommend_hook_path . $dir . '/' . $hookName);

            if (!is_readable($recommend_hook_path . $dir . '/hookDescription')) {
                return message(200, 0, '文件' . $recommend_hook_path . $dir . '/hookDescription' . '不可读');
            }
            $hookDescription = file_get_contents($recommend_hook_path . $dir . '/hookDescription');

            array_push($list, [
                'hookName' => $hookName,
                'hookContent' => $hookContent,
                'hookDescription' => $hookDescription
            ]);
        }

        return message(200, 1, '成功', $list);
    }

    /**
     * 获取简单SVN仓库列表
     */
    private function GetSimpleRepList()
    {
        funShellExec('chmod 777 -R ' . $this->configSvn['home_path']);

        $repArray = [];
        $file_arr = scandir($this->configSvn['rep_base_path']);
        foreach ($file_arr as $file_item) {
            clearstatcache();
            if ($file_item != '.' && $file_item != '..') {
                if (is_dir($this->configSvn['rep_base_path'] .  $file_item)) {
                    $file_arr2 = scandir($this->configSvn['rep_base_path'] .  $file_item);
                    foreach ($file_arr2 as $file_item2) {
                        if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                            array_push($repArray, $file_item);
                            break;
                        }
                    }
                }
            }
        }
        return $repArray;
    }

    /**
     * 初始化仓库结构为 trunk branches tags
     */
    private function InitRepStruct($templetePath, $repPath, $initUser = 'SVNAdmin', $initPass = 'SVNAdmin', $message = 'Initial structure')
    {
        $cmd = sprintf("'%s' import '%s' 'file:///%s' --quiet --username '%s' --password '%s' --message '%s'", $this->configBin['svn'], $templetePath, $repPath, $initUser, $initPass, $message);
        funShellExec($cmd);
    }

    /**
     * 获取仓库的修订版本数量
     * svnadmin info
     * 
     * Subversion 1.9 及以前没有 svnadmin info 子指令 
     * 因此使用 svnlook youngest 来代替
     */
    private function GetRepRev($repName)
    {
        // $cmd = sprintf("'%s' info '%s' | grep 'Revisions' | awk '{print $2}'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $repName);

        $cmd = sprintf("'%s' youngest '%s'", $this->configBin['svnlook'], $this->configSvn['rep_base_path'] .  $repName);

        $result = funShellExec($cmd);

        return (int)trim($result['result']);
    }

    /**
     * 获取仓库的UUID
     */
    private function GetRepUUID($repName)
    {
        $cmd = sprintf("'%s' uuid '%s'", $this->configBin['svnlook'], $this->configSvn['rep_base_path'] .  $repName);

        $result = funShellExec($cmd);

        return trim($result['result']);
    }

    /**
     * 获取仓库的属性内容（key-value的形式）
     * svnadmin info
     * 
     * Subversion 1.9 及以前没有 svnadmin info 子指令 
     */
    private function GetRepDetail110($repName)
    {
        $cmd = sprintf("'%s' info '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $repName);
        $result = funShellExec($cmd);
        return $result;
    }

    /**
     * 获取仓库下某个文件的体积
     * 
     * 目前为默认最新版本
     * 
     * 根据体积大小自动调整单位
     * 
     * svnlook file
     */
    private function GetRepRevFileSize($repName, $filePath)
    {
        $cmd = sprintf("'%s' filesize '%s' '%s'", $this->configBin['svnlook'], $this->configSvn['rep_base_path'] . $repName, $filePath);
        $result = funShellExec($cmd);
        $size = (int)$result['result'];
        return funFormatSize($size);
    }

    /**
     * 获取仓库下指定文件或者文件夹的最高修订版本
     * 
     * svnlook history
     * 
     * 是否有必要做错误捕获
     */
    private function GetRepFileRev($repName, $filePath)
    {
        $cmd = sprintf("'%s' history --limit 1 '%s' '%s'", $this->configBin['svnlook'], $this->configSvn['rep_base_path'] .  $repName, $filePath);
        $result = funShellExec($cmd);
        $result = $result['result'];
        $resultArray = explode("\n", $result);
        $content = preg_replace("/\s{2,}/", ' ', $resultArray[2]);
        $contentArray = explode(' ', $content);
        return trim($contentArray[1]);
    }

    /**
     * 获取仓库下指定文件或者文件夹的作者
     * 
     * svnlook author
     * 
     * 是否有必要做错误捕获
     */
    private function GetRepFileAuthor($repName, $rev)
    {
        $cmd = sprintf("'%s' author -r %s '%s'", $this->configBin['svnlook'], $rev, $this->configSvn['rep_base_path'] .  $repName);
        $result = funShellExec($cmd);
        return $result['result'];
    }

    /**
     * 获取仓库下指定文件或者文件夹的提交日期
     * 
     * svnlook date
     * 
     * 是否有必要做错误捕获
     */
    private function GetRepFileDate($repName, $rev)
    {
        $cmd = sprintf("'%s' date -r %s '%s'", $this->configBin['svnlook'], $rev, $this->configSvn['rep_base_path'] .  $repName);
        $result = funShellExec($cmd);
        return $result['result'];
    }

    /**
     * 获取仓库下指定文件或者文件夹的提交日志
     * 
     * svnlook log
     * 
     * 是否有必要做错误捕获
     */
    private function GetRepFileLog($repName, $rev)
    {
        $cmd = sprintf("'%s' log -r %s '%s'", $this->configBin['svnlook'], $rev, $this->configSvn['rep_base_path'] .  $repName);
        $result = funShellExec($cmd);
        return $result['result'];
    }

    /**
     * 以SVN用户身份获取 svn list 的结果
     */
    private function GetSvnList($path, $repName)
    {
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
        $svnUserPass = $this->SVNAdmin->GetUserInfo($this->passwdContent, $this->userName);
        if (is_numeric($svnUserPass)) {
            if ($svnUserPass == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($svnUserPass == 710) {
                return message(200, 0, '用户不存在');
            } else {
                return message(200, 0, "错误码$svnUserPass");
            }
        }

        /**
         * 使用svn list进行内容获取
         */
        $checkResult = $this->CheckSvnUserPathAutzh($checkoutHost, $repName, $path, $this->userName, $svnUserPass['userPass']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }
        $result = $checkResult['data'];

        return message(200, 1, '成功', $result);
    }

    /**
     * svn list执行
     */
    private function CheckSvnUserPathAutzh($checkoutHost, $repName, $repPath, $svnUserName, $svnUserPass)
    {
        $cmd = sprintf("'%s' list '%s' --username '%s' --password '%s' --no-auth-cache --non-interactive --trust-server-cert", $this->configBin['svn'], $checkoutHost . '/' . $repName . $repPath, $svnUserName, $svnUserPass);
        $result = funShellExec($cmd);

        if ($result['code'] != 0) {
            //: Authentication error from server: Password incorrect
            if (strstr($result['error'], 'svn: E170001') && strstr($result['error'], 'Password incorrect')) {
                return ['code' => 200, 'status' => 0, 'message' => '密码错误', 'data' => []];
            }
            //: Authorization failed
            if (strstr($result['error'], 'svn: E170001') && strstr($result['error'], 'Authorization failed')) {
                return ['code' => 200, 'status' => 0, 'message' => '无访问权限', 'data' => []];
            }
            //svn: E170001类型的其它错误
            if (strstr($result['error'], 'svn: E170001')) {
                return ['code' => 200, 'status' => 0, 'message' => '无访问权限-svn: E170001', 'data' => []];
            }
            //: Invalid authz configuration
            if (strstr($result['error'], 'svn: E220003')) {
                return ['code' => 200, 'status' => 0, 'message' => 'authz文件配置错误 请使用svnauthz-validate工具检查', 'data' => []];
            }
            //: Unable to connect to a repository at URL
            if (strstr($result['error'], 'svn: E170013')) {
                return ['code' => 200, 'status' => 0, 'message' => '无法连接到仓库', 'data' => []];
            }
            //: Could not list all targets because some targets don't exist
            if (strstr($result['error'], 'svn: warning: W160013') || strstr($result['error'], "svn: E200009")) {
                return ['code' => 200, 'status' => 0, 'message' => '该授权路径在仓库不存在 请刷新以同步', 'data' => []];
            }
            return ['code' => 200, 'status' => 0, 'message' => '认证出错' . $result['error'], 'data' => []];
        }

        return ['code' => 200, 'status' => 1, 'message' => '成功', 'data' => $result['result']];
    }
}
