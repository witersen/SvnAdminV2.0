<?php
/*
 * @Author: witersen
 * @Date: 2022-05-03 02:20:27
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 17:49:34
 * @Description: QQ:1801168257
 */

namespace app\controller;

use support\Request;

class Core
{
    //权限token
    public $token;

    //根据token得到的用户信息
    public $userName;
    public $userRoleId;

    //svn配置文件
    public $authzContent;
    public $passwdContent;

    //medoo
    public $database;

    //配置信息
    private $config_svnadmin_routers;
    private $config_svnadmin_database;
    public $config_svnadmin_version;
    public $config_svnadmin_update;
    public $config_svnadmin_svn;
    public $config_svnadmin_reg;
    public $config_svnadmin_sign;

    //payload
    public $payload;

    //SVNAdmin
    public $SVNAdminGroup;
    public $SVNAdminInfo;
    public $SVNAdminRep;
    public $SVNAdminUser;

    //检查
    public $checkService;

    //邮件
    public $mail;

    /**
     * 该方法会在请求前调用 
     */
    public function beforeAction(Request $request)
    {
        /**
         * 1、获取基本信息
         */
        //请求类型
        $type = $request->get('t');

        //控制器
        $controller = $request->controller;

        //方法
        $action = $request->action;

        //配置信息
        $this->config_svnadmin_routers = config('svnadmin_router');                 //路由
        $this->config_svnadmin_database = config('plugin.webman.medoo.database');   //数据库配置
        $this->config_svnadmin_version = config('svnadmin_version');                //版本
        $this->config_svnadmin_update = config('svnadmin_update');                  //升级检测
        $this->config_svnadmin_svn = config('svnadmin_svn');                        //仓库
        $this->config_svnadmin_reg = config('svnadmin_reg');                        //正则
        $this->config_svnadmin_sign = config('svnadmin_sign');                      //密钥

        //token
        $this->token = $request->header('token');

        /**
         * 2、检查接口类型
         */
        if (!in_array($type, array_keys($this->config_svnadmin_routers['public']))) {
            return message(401, 0, '无效的接口类型');
        }

        /**
         * 3、检查白名单路由
         */
        if (!in_array("$controller\\$action", $this->config_svnadmin_routers['public'][$type])) {
            //如果请求不在对应类型的白名单中 则需要进行token校验
            $result = $this->CheckToken();
            if ($result['status'] != 1) {
                //token校验不通过则返回
                return message($result['code'], $result['status'], $result['message']);
            }
        }

        /**
         * 4、用户信息获取
         */
        $this->GetUserInfo();

        /**
         * 5、检查特定角色权限路由
         */
        if ($this->userRoleId == 2) {
            if (!in_array("$controller\\$action", array_merge($this->config_svnadmin_routers['svn_user_routers'], $this->config_svnadmin_routers['public'][$type]))) {
                return message(401, 0, '无权限');
            }
        }

        /**
         * 6、获取数据库连接
         */
        $this->database = new \Medoo\Medoo($this->config_svnadmin_database['default']);

        /**
         * 7、检查token是否已注销
         */
        $black = $this->database->get('black_token', ['token_id'], ['token' => $this->token]);
        if ($black != null) {
            return message(401, 0, 'token已注销');
        }

        /**
         * 8、获取authz和passwd的配置文件信息
         */
        $this->GetAuthz();
        $this->GetPasswd();

        /**
         * 9、获取payload
         */
        $this->payload = !empty($request->rawBody()) ? json_decode($request->rawBody(), true) : [];

        /**
         * 10、svnadmin对象
         */
        $this->SVNAdminGroup = new \SVNAdmin\SVN\Group($this->authzContent, $this->passwdContent, $this->config_svnadmin_svn);
        $this->SVNAdminRep = new \SVNAdmin\SVN\Rep($this->authzContent, $this->passwdContent, $this->config_svnadmin_svn);
        $this->SVNAdminUser = new \SVNAdmin\SVN\User($this->authzContent, $this->passwdContent, $this->config_svnadmin_svn);

        /**
         * 11、检查对象
         */
        $this->checkService = new \CheckService();

        /**
         * 12、邮件对象
         */
        $this->mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $this->mail->setLanguage('zh_cn', BASE_PATH . '/extension/PHPMailer-6.6.0/language/'); //加载错误消息翻译包
    }

    /**
     * 根据token获取用户信息
     */
    private function GetUserInfo()
    {
        if ($this->token == null || $this->token == '') {
            $this->userRoleId = 0;
            $this->userName = '';
            return;
        }

        $array = explode('.', $this->token);

        $this->userRoleId = $array[0];
        $this->userName = $array[1];
    }

    /**
     * 生成token
     *
     * @param int $userRoleId
     * @param string $userName
     * @return string
     */
    public function CreateToken($userRoleId, $userName)
    {
        $nowTime = time();

        $startTime = $nowTime;

        //配置登录凭证过期时间为6个小时
        $endTime = $nowTime + 60 * 60 * 6;

        $part1 = $userRoleId . '.' . $userName . '.' . $startTime . '.' . $endTime;

        $part2 = hash_hmac('md5', $part1, $this->config_svnadmin_sign['signature']);

        return $part1 . '.' . $part2;
    }

    /**
     * 校验token
     *
     * @return void
     */
    public function CheckToken()
    {
        //判断是否为空
        if ($this->token == null || $this->token == '') {
            return [
                'code' => 401,
                'status' => 0,
                'message' => '非法请求1',
                'data' => []
            ];
        }

        //校验token格式
        if (substr_count($this->token, '.') != 4) {
            return [
                'code' => 401,
                'status' => 0,
                'message' => '非法请求2',
                'data' => []
            ];
        }

        $arr = explode('.', $this->token);

        //校验token格式
        foreach ($arr as $value) {
            if (trim($value) == '') {
                return [
                    'code' => 401,
                    'status' => 0,
                    'message' => '非法请求3',
                    'data' => []
                ];
            }
        }

        //检验token内容
        $part1 =  hash_hmac('md5', $arr[0] . '.' . $arr[1] . '.' . $arr[2] . '.' . $arr[3], $this->config_svnadmin_sign['signature']);
        $part2 = $arr[4];
        if ($part1 != $part2) {
            return [
                'code' => 401,
                'status' => 0,
                'message' => '非法请求4',
                'data' => []
            ];
        }

        //校验是否过期
        if (time() > $arr[3]) {
            return [
                'code' => 401,
                'status' => 0,
                'message' => '登陆过期',
                'data' => []
            ];
        }

        return [
            'code' => 200,
            'status' => 1,
            'message' => '校验通过',
            'data' => []
        ];
    }

    /**
     * 从authz文件中读取内容
     * 
     * 由于有些操作会更改authz文件内容且其它操作依赖这一实时结果 因此需要及时更新
     */
    public function GetAuthz()
    {
        $this->authzContent = file_exists($this->config_svnadmin_svn['svn_authz_file']) ? file_get_contents($this->config_svnadmin_svn['svn_authz_file']) : '';
    }

    /**
     * 从passwd文件中读取内容
     */
    public function GetPasswd()
    {
        $this->passwdContent = file_exists($this->config_svnadmin_svn['svn_passwd_file']) ? file_get_contents($this->config_svnadmin_svn['svn_passwd_file']) : '';
    }

    /**
     * 写入日志
     */
    public function InsertLog($log_type_name = '', $log_content = '', $log_add_user_name = '')
    {
        $this->database->insert('logs', [
            'log_type_name' => $log_type_name,
            'log_content' => $log_content,
            'log_add_user_name' => $log_add_user_name,
            'log_add_time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 获取svnserve端口和主机情况
     * 
     * 先从svnserve配置文件获取绑定端口和主机
     * 然后向数据库同步
     * 
     * 绑定端口
     * 绑定地址
     * 管理地址
     * 检出地址的启用地址
     */
    public function GetSvnserveListen()
    {
        $bindPort = '';
        $bindHost = '';

        $svnserveContent = shellPassthru(sprintf("cat '%s'",  $this->config_svnadmin_svn['svnserve_env_file']));
        $svnserveContent = $svnserveContent['result'];

        //匹配端口
        if (preg_match('/--listen-port[\s]+([0-9]+)/', $svnserveContent, $portMatchs) != 0) {
            $bindPort = trim($portMatchs[1]);
        }

        //匹配地址
        if (preg_match('/--listen-host[\s]+([\S]+)\b/', $svnserveContent, $hostMatchs) != 0) {
            $bindHost = trim($hostMatchs[1]);
        }

        $svnserve_listen = $this->database->get('options', [
            'option_value'
        ], [
            'option_name' => 'svnserve_listen'
        ]);

        $insert = [
            "bindPort" => $bindPort == '' ? 3690 : $bindPort,
            "bindHost" => $bindHost == '' ? '0.0.0.0' : $bindHost,
            "manageHost" => "127.0.0.1",
            "enable" => "manageHost"
        ];

        if ($svnserve_listen == null) {
            //插入
            $this->database->insert('options', [
                'option_name' => 'svnserve_listen',
                'option_value' => serialize($insert),
                'option_description' => ''
            ]);
        } else if ($svnserve_listen['option_value'] == '') {
            //更新
            $this->database->update('options', [
                'option_value' => serialize($insert),
            ], [
                'option_name' => 'svnserve_listen',
            ]);
        } else {
            //更新
            $svnserve_listen = unserialize($svnserve_listen['option_value']);
            $insert['manageHost'] = $svnserve_listen['manageHost'] == '' ? '127.0.0.1' : $svnserve_listen['manageHost'];
            $insert['enable'] = $svnserve_listen['enable'] == '' ? 'manageHost' : $svnserve_listen['enable'];
            $this->database->update('options', [
                'option_value' => serialize($insert),
            ], [
                'option_name' => 'svnserve_listen',
            ]);
        }

        return $insert;
    }

    /**
     * 将SVN用户数据同步到数据库
     * 
     * 目的为维护用户启用状态和自定义备注信息
     */
    public function SyncUserToDb()
    {
        $svnUserPassList =  $this->SVNAdminUser->GetSvnUserPassList($this->passwdContent);
        if ($svnUserPassList == 0) {
            return ['code' => 200, 'status' => 0, 'message' => '文件格式错误(不存在[users]标识)', 'data' => []];
        }
        $dbUserPassList = $this->database->select('svn_users', [
            'svn_user_id',
            'svn_user_name',
            'svn_user_pass',
            'svn_user_status',
            'svn_user_note'
        ]);

        $combinArray1 = array_combine(FunArrayColumn($svnUserPassList, 'userName'), FunArrayColumn($svnUserPassList, 'disabled'));
        $combinArray2 = array_combine(FunArrayColumn($svnUserPassList, 'userName'), FunArrayColumn($svnUserPassList, 'userPass'));
        foreach ($dbUserPassList as $value) {
            if (!in_array($value['svn_user_name'], FunArrayColumn($svnUserPassList, 'userName'))) {
                $this->database->delete('svn_users', [
                    'svn_user_name' => $value['svn_user_name']
                ]);
            } else {
                //更新启用状态和密码
                $this->database->update('svn_users', [
                    'svn_user_pass' => $combinArray2[$value['svn_user_name']],
                    'svn_user_status' => !$combinArray1[$value['svn_user_name']]
                ], [
                    'svn_user_name' => $value['svn_user_name']
                ]);
            }
        }

        foreach ($svnUserPassList as $value) {
            if (!in_array($value['userName'], FunArrayColumn($dbUserPassList, 'svn_user_name'))) {
                $this->database->insert('svn_users', [
                    'svn_user_name' => $value['userName'],
                    'svn_user_pass' => $value['userPass'],
                    'svn_user_status' => !$value['disabled'],
                    'svn_user_note' => ''
                ]);
            }
        }

        return ['code' => 200, 'status' => 1, 'message' => '', 'data' => []];
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
    public function GetSvnUserAllGroupList($userName)
    {
        $authzContent = $this->authzContent;

        //所有的分组列表
        $allGroupList = $this->SVNAdminGroup->GetSvnGroupList($authzContent);

        //用户所在的分组列表
        $userGroupList = $this->SVNAdminUser->GetSvnUserGroupList($authzContent, $userName);

        //剩余的分组列表
        $leftGroupList = array_diff($allGroupList, $userGroupList);

        //循环匹配 直到匹配到与该用户相关的有权限的用户组为止
        loop:
        $userGroupListBack = $userGroupList;
        foreach ($userGroupList as $group1) {
            $newList = $this->SVNAdminGroup->GetSvnGroupGroupList($authzContent, $group1);
            foreach ($leftGroupList as $key2 => $group2) {
                if (in_array($group2, $newList)) {
                    array_push($userGroupList, $group2);
                    unset($leftGroupList[$key2]);
                }
            }
        }
        if ($userGroupList != $userGroupListBack) {
            goto loop;
        }

        return $userGroupList;
    }
}
