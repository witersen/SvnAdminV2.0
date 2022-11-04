<?php
/*
 * @Author: witersen
 * @Date: 2022-05-06 18:41:32
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 13:15:21
 * @Description: QQ:1801168257
 */

namespace app\controller;

//require config
auto_require(BASE_PATH . '/config/');

//require function
// auto_require(BASE_PATH . '/app/function/');

//require util
// auto_require(BASE_PATH . '/app/util/');

//require controller
auto_require(BASE_PATH . '/app/controller/');

//require service
auto_require(BASE_PATH . '/app/service/base/Base.php');
auto_require(BASE_PATH . '/app/service/');

//require extension
auto_require(BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php');

// auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/Exception.php');
// auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/PHPMailer.php');
// auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/SMTP.php');
// auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/language/phpmailer.lang-zh_cn.php');

// auto_require(BASE_PATH . '/extension/Verifycode/Verifycode.php');

// auto_require(BASE_PATH . '/extension/Witersen/SVNAdmin.php');

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

use Config;

use Medoo\Medoo;

class Base
{
    public $param;

    function __construct($parm)
    {
        $this->param = $parm;

        //配置信息
        $configRouters =  Config::get('router');                //路由
        $configDatabase = Config::get('database');              //数据库配置
        $configSvn = Config::get('svn');                        //仓库
        $configSign = Config::get('sign');                      //密钥

        /**
         * 2、检查接口类型
         */
        !in_array($parm['type'], array_keys($configRouters['public'])) ? json1(401, 0, '无效的接口类型') : '';

        /**
         * 3、检查白名单路由
         */
        if (!in_array($parm['controller_prefix'] . '/' . $parm['action'], $configRouters['public'][$parm['type']])) {
            /**
             * 如果请求不在对应类型的白名单中 则需要进行token校验
             */

            //判断是否为空
            empty($parm['token']) ? json1(401, 0, 'token为空') : '';

            //校验token格式
            substr_count($parm['token'], $configSign['signSeparator']) != 4 ? json1(401, 0, 'token格式错误') : '';

            $arr = explode($configSign['signSeparator'], $parm['token']);

            //校验token格式
            foreach ($arr as $value) {
                trim($value) == '' ? json1(401, 0, 'token格式错误') : '';
            }

            //检验token内容
            $part1 =  hash_hmac('md5', $arr[0] . $configSign['signSeparator'] . $arr[1] . $configSign['signSeparator'] . $arr[2] . $configSign['signSeparator'] . $arr[3], $configSign['signature']);
            $part2 = $arr[4];
            $part1 != $part2 ? json1(401, 0, 'token校验失败') : '';

            //校验是否过期
            time() > $arr[3] ? json1(401, 0, '登陆过期') : '';
        }

        /**
         * 5、检查特定角色权限路由
         */
        if (empty($parm['token'])) {
            $userRoleId = 0;
        } else {
            $array = explode($configSign['signSeparator'], $parm['token']);
            $userRoleId = $array[0];
        }
        if ($userRoleId == 2) {
            if (!in_array($parm['controller_prefix'] . '/' . $parm['action'], array_merge($configRouters['svn_user_routers'], $configRouters['public'][$parm['type']]))) {
                json1(401, 0, '无权限');
            }
        }

        /**
         * 7、检查token是否已注销
         */
        if (array_key_exists('database_file', $configDatabase)) {
            $configDatabase['database_file'] = sprintf($configDatabase['database_file'], $configSvn['home_path']);
        }
        try {
            $black = (new Medoo($configDatabase))->get('black_token', ['token_id'], ['token' => $parm['token']]);
            !empty($black) ? json1(401, 0, 'token已注销') : '';
        } catch (\Exception $e) {
            json1(200, 0, $e->getMessage());
        }
    }
}
