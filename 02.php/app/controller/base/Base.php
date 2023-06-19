<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
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

class Base
{
    public $param;

    function __construct($parm)
    {
        $this->param = $parm;

        //配置信息
        $configRouters =  Config::get('router');                //路由
        $configSvn = Config::get('svn');                        //仓库
        $configSign = Config::get('sign');                      //密钥

        global $database;

        /**
         * 2、检查接口类型
         */
        !in_array($parm['type'], array_keys($configRouters['public'])) ? json1(401, 0, '无效的接口类型') : '';

        if (!in_array($parm['controller_prefix'] . '/' . $parm['action'], $configRouters['public'][$parm['type']])) {
            /**
             * 3、检查白名单路由
             * 
             * 如果请求不在对应类型的白名单中 则需要进行token校验
             */

            empty($parm['token']) ? json1(401, 0, 'token为空') : '';

            substr_count($parm['token'], $configSign['signSeparator']) != 4 ? json1(401, 0, 'token格式错误') : '';
            $arr = explode($configSign['signSeparator'], $parm['token']);
            foreach ($arr as $value) {
                trim($value) == '' ? json1(401, 0, 'token格式错误') : '';
            }

            $part1 =  hash_hmac('md5', $arr[0] . $configSign['signSeparator'] . $arr[1] . $configSign['signSeparator'] . $arr[2] . $configSign['signSeparator'] . $arr[3], $configSign['signature']);
            $part2 = $arr[4];
            $part1 != $part2 ? json1(401, 0, 'token校验失败') : '';

            time() > $arr[3] ? json1(401, 0, '登陆过期') : '';

            /**
             * 5、检查特定角色权限路由
             */
            $info = explode($configSign['signSeparator'], $parm['token']);
            $userRoleId = $info[0];
            $userName = $info[1];
            if ($userRoleId == 2) {
                if (!in_array($parm['controller_prefix'] . '/' . $parm['action'], array_merge($configRouters['svn_user_routers'], $configRouters['public'][$parm['type']]))) {
                    json1(401, 0, '无权限');
                }
            } elseif ($userRoleId == 3) {
                $subadminFunctions = $database->get('subadmin', 'subadmin_functions', [
                    'subadmin_name' => $userName
                ]);
                $subadminFunctions = json_decode($subadminFunctions, true);
                if (empty($subadminFunctions) || !in_array($parm['controller_prefix'] . '/' . $parm['action'], $subadminFunctions)) {
                    json1(403, 0, '权限未分配');
                }
            }

            /**
             * 7、检查是否被顶掉
             */
            if ($userRoleId == 1) {
                $status = $database->get('admin_users', 'admin_user_token', [
                    'admin_user_name' => $userName,
                ]);
            } elseif ($userRoleId == 2) {
                $status = $database->get('svn_users', 'svn_user_token', [
                    'svn_user_name' => $userName,
                ]);
            } elseif ($userRoleId == 3) {
                $status = $database->get('subadmin', 'subadmin_token', [
                    'subadmin_name' => $userName,
                ]);
            }
            if (!empty($status)) {
                if ($status != $parm['token']) {
                    json1(401, 0, '当前账户在其它设备登录');
                }
            }

            /**
             * 8、检查token是否已注销
             */
            $black = $database->get('black_token', 'token_id', ['token' => $parm['token']]);
            !empty($black) ? json1(401, 0, 'token已注销') : '';
        }

        /**
         * 9、检查关键目录/文件是否可读写
         */
        if ($parm['controller_prefix'] . '/' . $parm['action'] == 'Common/Login') {
            $continue = [
                'svnserve_pid_file',
                'httpd_pid_file',
                'saslauthd_pid_file',
                'apache_modules_path',
                'apache_subversion_file'
            ];
            foreach ($configSvn as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                if (in_array($key, $continue)) {
                    continue;
                }
                if (substr($key, -5) == '_path') {
                    if (!is_writable($value)) {
                        json1(200, 0, sprintf('目录[%s]不存在或不可写', $value));
                    }
                } elseif (substr($key, -5) == '_file') {
                    if (!is_writable($value)) {
                        json1(200, 0, sprintf('文件[%s]不存在或不可写', $value));
                    }
                }
            }
        }
    }
}
