<?php
/*
 * @Author: witersen
 * @Date: 2022-05-06 18:42:00
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-09 21:10:15
 * @Description: QQ:1801168257
 */

namespace app\service;

use Check;

use Config;

use Medoo\Medoo;

use SVNAdmin\SVN\Group;
use SVNAdmin\SVN\Rep;
use SVNAdmin\SVN\User;

class Base
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
    public $config_bin;
    private $config_routers;
    private $config_database;
    public $config_version;
    public $config_update;
    public $config_svn;
    public $config_reg;
    public $config_sign;

    //payload
    public $payload;

    //SVNAdmin
    public $SVNAdminGroup;
    public $SVNAdminInfo;
    public $SVNAdminRep;
    public $SVNAdminUser;

    //检查
    public $checkService;

    function __construct()
    {
        global $token;
        global $type;
        global $controller_perifx;
        global $action;
        global $payload;

        //配置信息
        $this->config_bin =  Config::get('bin');                       //可执行文件路径
        $this->config_routers =  Config::get('router');                //路由
        $this->config_database = Config::get('database');              //数据库配置
        $this->config_version = Config::get('version');                //版本
        $this->config_update = Config::get('update');                  //升级检测
        $this->config_svn = Config::get('svn');                        //仓库
        $this->config_reg = Config::get('reg');                        //正则
        $this->config_sign = Config::get('sign');                      //密钥

        //token
        $this->token = $token;

        /**
         * 2、检查接口类型
         */
        if (!in_array($type, array_keys($this->config_routers['public']))) {
            json1(401, 0, '无效的接口类型');
        }

        /**
         * 3、检查白名单路由
         */
        if (!in_array("$controller_perifx/$action", $this->config_routers['public'][$type])) {
            //如果请求不在对应类型的白名单中 则需要进行token校验
            $result = $this->CheckToken();
            if ($result['status'] != 1) {
                //token校验不通过则返回
                json1($result['code'], $result['status'], $result['message']);
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
            if (!in_array("$controller_perifx/$action", array_merge($this->config_routers['svn_user_routers'], $this->config_routers['public'][$type]))) {
                json1(401, 0, '无权限');
            }
        }

        /**
         * 6、获取数据库连接
         */
        if (array_key_exists('database_file', $this->config_database)) {
            $this->config_database['database_file'] = sprintf($this->config_database['database_file'], $this->config_svn['home_path']);
        }
        $this->database = new Medoo($this->config_database);

        /**
         * 7、检查token是否已注销
         */
        $black = $this->database->get('black_token', ['token_id'], ['token' => $this->token]);
        if ($black != null) {
            json1(401, 0, 'token已注销');
        }

        /**
         * 8、获取authz和passwd的配置文件信息
         */
        $this->GetAuthz();
        $this->GetPasswd();

        /**
         * 9、获取payload
         */
        $this->payload = $payload;

        /**
         * 10、svnadmin对象
         */
        $this->SVNAdminGroup = new Group($this->authzContent, $this->passwdContent, $this->config_svn, $this->config_bin);
        $this->SVNAdminRep = new Rep($this->authzContent, $this->passwdContent, $this->config_svn, $this->config_bin);
        $this->SVNAdminUser = new User($this->authzContent, $this->passwdContent, $this->config_svn, $this->config_bin);

        /**
         * 11、检查对象
         */
        $this->checkService = new Check($this->config_reg);
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

        $part2 = hash_hmac('md5', $part1, $this->config_sign['signature']);

        return $part1 . '.' . $part2;
    }

    /**
     * 校验token
     *
     * @return void
     */
    private function CheckToken()
    {
        //判断是否为空
        if ($this->token == null || $this->token == '') {
            return [
                'code' => 401,
                'status' => 0,
                'message' => 'token为空',
                'data' => []
            ];
        }

        //校验token格式
        if (substr_count($this->token, '.') != 4) {
            return [
                'code' => 401,
                'status' => 0,
                'message' => 'token格式错误',
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
                    'message' => 'token格式错误',
                    'data' => []
                ];
            }
        }

        //检验token内容
        $part1 =  hash_hmac('md5', $arr[0] . '.' . $arr[1] . '.' . $arr[2] . '.' . $arr[3], $this->config_sign['signature']);
        $part2 = $arr[4];
        if ($part1 != $part2) {
            return [
                'code' => 401,
                'status' => 0,
                'message' => 'token校验失败',
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
        $this->authzContent = file_exists($this->config_svn['svn_authz_file']) ? file_get_contents($this->config_svn['svn_authz_file']) : '';
    }

    /**
     * 从passwd文件中读取内容
     */
    public function GetPasswd()
    {
        $this->passwdContent = file_exists($this->config_svn['svn_passwd_file']) ? file_get_contents($this->config_svn['svn_passwd_file']) : '';
    }
}
