<?php
/*
 * @Author: witersen
 * @Date: 2022-05-06 18:42:00
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-27 22:31:40
 * @Description: QQ:1801168257
 */

namespace app\service;

//require config
auto_require(BASE_PATH . '/config/');

//require function
auto_require(BASE_PATH . '/app/function/');

//require util
auto_require(BASE_PATH . '/app/util/');

//require service
auto_require(BASE_PATH . '/app/service/');

//require extension
auto_require(BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php');

auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/Exception.php');
auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/PHPMailer.php');
auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/SMTP.php');
auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/language/phpmailer.lang-zh_cn.php');

auto_require(BASE_PATH . '/extension/Verifycode/Verifycode.php');

auto_require(BASE_PATH . '/extension/Witersen/SVNAdmin.php');

function auto_require($path, $recursively = false)
{
    if (is_file($path)) {
        if (substr($path, -4) == '.php') {
            require_once $path;
        }
    } else {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . '/' . $file)) {
                    $recursively ? auto_require($path . '/' . $file, true) : '';
                } else {
                    if (substr($file, -4) == '.php') {
                        require_once $path . '/' . $file;
                    }
                }
            }
        }
    }
}

use Check;

use Config;

use Medoo\Medoo;

use Witersen\SVNAdmin;
use SVNAdmin\SVN\Group;
use SVNAdmin\SVN\Rep;
use SVNAdmin\SVN\User;

class Base
{
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
    public $configBin;
    public $configSvn;
    public $configReg;
    public $configSign;

    //payload
    public $payload;

    //SVNAdmin
    public $SVNAdmin;
    public $SVNAdminGroup;
    public $SVNAdminInfo;
    public $SVNAdminRep;
    public $SVNAdminUser;

    //检查
    public $checkService;

    function __construct($parm)
    {
        //配置信息
        $this->configBin =  Config::get('bin');                       //可执行文件路径
        $configDatabase = Config::get('database');              //数据库配置
        $this->configSvn = Config::get('svn');                        //仓库
        $this->configReg = Config::get('reg');                        //正则
        $this->configSign = Config::get('sign');                      //密钥

        $this->token = isset($parm['token']) ? $parm['token'] : '';

        /**
         * 4、用户信息获取
         */
        if (empty($this->token)) {
            $this->userRoleId = isset($parm['payload']['userRoleId']) ? $parm['payload']['userRoleId'] : 0;
            $this->userName = isset($parm['payload']['userName']) ? $parm['payload']['userName'] : 0;
        } else {
            $array = explode($this->configSign['signSeparator'], $this->token);
            $this->userRoleId = $array[0];
            $this->userName = $array[1];
        }

        /**
         * 6、获取数据库连接
         */
        if (array_key_exists('database_file', $configDatabase)) {
            $configDatabase['database_file'] = sprintf($configDatabase['database_file'], $this->configSvn['home_path']);
        }
        $this->database = new Medoo($configDatabase);

        /**
         * 8、获取authz和passwd的配置文件信息
         */
        $this->authzContent = file_get_contents($this->configSvn['svn_authz_file']);
        $this->passwdContent = file_get_contents($this->configSvn['svn_passwd_file']);

        /**
         * 9、获取payload
         */
        $this->payload = isset($parm['payload']) ? $parm['payload'] : [];

        /**
         * 10、svnadmin对象
         */
        $this->SVNAdmin = new SVNAdmin();

        /**
         * 11、检查对象
         */
        $this->checkService = new Check($this->configReg);
    }
}
