<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 18:12:08
 * @Description: QQ:1801168257
 */

namespace app\service;

use Config;

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
        funShellExec($cmd);

        //关闭selinux
        funShellExec('setenforce 0');

        if ($this->payload['rep_type'] == '2') {
            //以指定的目录结构初始化仓库
            $this->SVNAdminRep->InitRepStruct($this->config_svn['templete_init_struct_01'], $this->config_svn['rep_base_path'] . $this->payload['rep_name']);
        }

        //检查是否创建成功
        $checkResult = $this->SVNAdminRep->CheckRepCreate($this->payload['rep_name']);
        if ($checkResult['status'] != 1) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'], $checkResult['data']);
        }

        //向authz写入仓库信息
        $result = $this->SVNAdmin->WriteRepPathToAuthz($this->authzContent, $this->payload['rep_name'], '/');
        if (is_numeric($result)) {
            if ($result == 851) {
                $result = $this->authzContent;
            } else {
                return message(200, 0, "同步到配置文件错误$result");
            }
        }
        funFilePutContents($this->config_svn['svn_authz_file'], $result);

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
            if (!in_array($value, array_column($dbRepList, 'rep_name'))) {
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
            funFilePutContents($this->config_svn['svn_authz_file'], $authzContet);
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
            $cmd = sprintf("'%s' tree  '%s' --full-paths --non-recursive '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path'] .  $value['repName'], $value['priPath']);
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
            funFilePutContents($this->config_svn['svn_authz_file'], $authzContent);
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
        $sync = $this->payload['sync'];

        if ($sync == 'yes') {
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
        }

        $pageSize = $this->payload['pageSize'];
        $currentPage = $this->payload['currentPage'];
        $searchKeyword = trim($this->payload['searchKeyword']);

        //分页
        $begin = $pageSize * ($currentPage - 1);

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
        $this->authzContent = file_get_contents($this->config_svn['svn_authz_file']);

        /**
         * 对用户有权限的仓库路径列表进行一一验证
         * 
         * 确保该仓库的路径存在于仓库的最新版本库中
         */
        $this->SyncRepPathCheck();

        /**
         * 及时更新
         */
        $this->authzContent = file_get_contents($this->config_svn['svn_authz_file']);

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
        $checkResult = $this->SVNAdminRep->CheckSvnUserPathAutzh($checkoutHost, $repName, $path, $this->userName, $svnUserPass['userPass']);
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
        $checkResult = $this->SVNAdminRep->CheckRepExist($this->payload['rep_name']);
        if ($checkResult['status'] != 0) {
            return ['code' => 200, 'status' => 0, 'message' => '仓库不存在-请主动同步仓库', 'data' => []];
        }

        /**
         * 有权限的开始路径
         * 
         * 管理员为 /
         * SVN用户为管理员设定的路径值
         */
        $path = $this->payload['path'];

        //获取全路径的一层目录树
        $cmdSvnlookTree = sprintf("'%s' tree  '%s' --full-paths --non-recursive '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path'] .  $this->payload['rep_name'], $path);
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
     */
    public function GetRepTree()
    {
        //检查仓库是否存在
        $checkResult = $this->SVNAdminRep->CheckRepExist($this->payload['rep_name']);
        if ($checkResult['status'] != 0) {
            return ['code' => 200, 'status' => 0, 'message' => '仓库不存在-请主动同步仓库', 'data' => []];
        }

        $path = $this->payload['path'];

        //获取全路径的一层目录树
        $cmdSvnlookTree = sprintf("'%s' tree  '%s' --full-paths --non-recursive '%s'", $this->config_bin['svnlook'], $this->config_svn['rep_base_path']  . $this->payload['rep_name'], $path);
        $result = funShellExec($cmdSvnlookTree);
        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }
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
        array_multisort(array_column($data, 'resourceType'), SORT_DESC, $data);

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
     * 获取某个仓库路径的所有权限列表
     */
    public function GetRepPathAllPri()
    {
        //检查仓库是否存在
        $checkResult = $this->SVNAdminRep->CheckRepExist($this->payload['rep_name']);
        if ($checkResult['status'] != 0) {
            return ['code' => 200, 'status' => 0, 'message' => '仓库不存在-请主动同步仓库', 'data' => []];
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
        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $objectType = $this->payload['objectType'];
        $objectPri = $this->payload['objectPri'];
        $objectName = $this->payload['objectName'];

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
                return message(200, 0, "对象已有授权记录");
            } else if ($result == 901) {
                return message(200, 0, "不支持的授权对象类型");
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        //写入
        funFilePutContents($this->config_svn['svn_authz_file'], $result);

        //返回
        return message();
    }

    /**
     * 修改某个仓库路径下的权限
     */
    public function EditRepPathPri()
    {
        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $objectType = $this->payload['objectType'];
        $invert = $this->payload['invert'];
        $objectName = $this->payload['objectName'];
        $objectPri = $this->payload['objectPri'];

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
        funFilePutContents($this->config_svn['svn_authz_file'], $result);

        //返回
        return message();
    }

    /**
     * 删除某个仓库下的权限
     */
    public function DelRepPathPri()
    {
        $repName = $this->payload['rep_name'];
        $path = $this->payload['path'];
        $objectType = $this->payload['objectType'];
        $objectName = $this->payload['objectName'];

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
        funFilePutContents($this->config_svn['svn_authz_file'], $result);

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
        funShellExec('mv ' . $this->config_svn['rep_base_path'] .  $this->payload['old_rep_name'] . ' ' . $this->config_svn['rep_base_path'] . $this->payload['new_rep_name']);

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
        $result = $this->SVNAdmin->UpdRepFromAuthz($this->authzContent, $this->payload['old_rep_name'], $this->payload['new_rep_name']);
        if (is_numeric($result)) {
            if ($result == 751) {
                return message(200, 0, '仓库不存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        funFilePutContents($this->config_svn['svn_authz_file'], $result);

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
        $authzContet = $this->SVNAdmin->DelRepFromAuthz($this->authzContent, $this->payload['rep_name']);
        if (is_numeric($authzContet)) {
            if ($authzContet == 751) {
            } else {
                return message(200, 0, "错误码$authzContet");
            }
        } else {
            funFilePutContents($this->config_svn['svn_authz_file'], $authzContet);
        }

        //从数据库中删除
        $this->database->delete('svn_reps', [
            'rep_name' => $this->payload['rep_name']
        ]);

        //从仓库目录删除仓库文件夹
        funShellExec('cd ' . $this->config_svn['rep_base_path'] . ' && rm -rf ./' . $this->payload['rep_name']);
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
        //检查仓库是否存在
        $checkResult = $this->SVNAdminRep->CheckRepExist($this->payload['rep_name']);
        if ($checkResult['status'] != 0) {
            return ['code' => 200, 'status' => 0, 'message' => '仓库不存在-请主动同步仓库', 'data' => []];
        }

        $result = $this->SVNAdminRep->GetRepDetail110($this->payload['rep_name']);
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
                    'repValue' => $this->config_svn['rep_base_path'] .  $this->payload['rep_name'],
                ],
                [
                    'repKey' => 'UUID',
                    'repValue' => $this->SVNAdminRep->GetRepUUID($this->payload['rep_name'])
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
            $cmd = sprintf("'%s' setuuid '%s'", $this->config_bin['svnadmin'], $this->config_svn['rep_base_path'] .  $this->payload['rep_name']);
        } else {
            $cmd = sprintf("'%s' setuuid '%s' '%s'", $this->config_bin['svnadmin'], $this->config_svn['rep_base_path'] .  $this->payload['rep_name'], $this->payload['uuid']);
        }

        $result = funShellExec($cmd);

        if ($result['code'] == 0) {
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
        $result = FunGetDirFileList($this->config_svn['backup_base_path']);

        foreach ($result as $key => $value) {
            $result[$key]['fileToken'] = hash_hmac('md5', $value['fileName'], $this->config_sign['signature']);
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
        $checkResult = $this->SVNAdminRep->CheckRepExist($this->payload['rep_name']);
        if ($checkResult['status'] != 0) {
            return ['code' => 200, 'status' => 0, 'message' => '仓库不存在-请主动同步仓库', 'data' => []];
        }

        $result = $this->SVNAdminRep->RepDump($this->payload['rep_name'], $this->payload['rep_name'] . '_' . date('YmdHis') . '_' . uniqid() . FunGetRandStr() . '.dump');

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
        $this->SVNAdminRep->DelRepBackup($this->payload['fileName']);

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
        $filePath = $this->config_svn['backup_base_path'] .  $fileName;

        if (empty($_GET['token'])) {
            json1(200, 0, '缺少文件token');
        }
        $token = $_GET['token'];

        if ($token !== hash_hmac('md5', $fileName, $this->config_sign['signature'])) {
            json1(200, 0, '文件token无效');
        }

        if (!file_exists($this->config_svn['backup_base_path'] .  $fileName)) {
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
            $localFilePath = $this->config_svn['backup_base_path'] .  $fileName;

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

        funShellExec(sprintf("cd '%s' && rm -f ./'%s'", $hooksPath, $this->payload['fileName']));

        return message(200, 1, '移除成功');
    }

    /**
     * 修改仓库的某个钩子内容
     */
    public function EditRepHook()
    {
        $hooksPath = $this->config_svn['rep_base_path'] . $this->payload['rep_name'] . '/hooks/';

        //使用echo写入文件 当出现不规则的不成对的 ' " 等会出问题 当然也会包括其他问题
        funFilePutContents($hooksPath . $this->payload['fileName'], $this->payload['content']);

        funShellExec('chmod 777 -R ' . $this->config_svn['home_path']);

        return message();
    }

    /**
     * 获取常用钩子列表
     */
    public function GetRecommendHooks()
    {
        clearstatcache();

        $list = [];

        $recommend_hook_path = $this->config_svn['recommend_hook_path'];
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
}
