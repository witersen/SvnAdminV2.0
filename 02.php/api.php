<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 11:38:15
 * @Description: QQ:1801168257
 */

/**
 * 需要PHP版本大于等于5.5同时小于8.0
 */

/**
 * 开启错误信息 如需要调试 可取消注释
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

define('BASE_PATH', __DIR__);

date_default_timezone_set('PRC');

require_once BASE_PATH . '/app/controller/base/Base.php';

Config::load(BASE_PATH . '/config/');

/**
 * token
 */
$token = empty($_SERVER['HTTP_TOKEN']) ? '' : $_SERVER['HTTP_TOKEN'];

/**
 * 控制器
 */
$controller_perifx = empty($_GET['c']) ? '' : $_GET['c'];
$controller = "\app\controller\\$controller_perifx";
$controller_path = BASE_PATH . '/app/controller/' . $controller_perifx . '.php';

/**
 * 方法
 */
$action = empty($_GET['a']) ? '' : $_GET['a'];

/**
 * 接口类型
 * 小程序还是web系统
 */
$type = empty($_GET['t']) ? '' : $_GET['t'];

/**
 * 请求参数即Request Payload
 * Content-Type: application/json
 */
$payload = file_get_contents("php://input");
$payload = !empty($payload) ? json_decode($payload, true) : [];

//检测PHP版本
$version = Config::get('version');
if (isset($version['php']['lowest']) && !empty($version['php']['lowest'])) {
    if (PHP_VERSION < $version['php']['lowest']) {
        json1(200, 0, sprintf('支持的最低PHP版本为[%s]当前的PHP版本为[%s]', $version['php']['lowest'], PHP_VERSION));
    }
}
if (isset($version['php']['highest']) && !empty($version['php']['highest'])) {
    if (PHP_VERSION >= $version['php']['highest']) {
        json1(200, 0, sprintf('支持的最高PHP版本为[%s]当前的PHP版本为[%s]', $version['php']['highest'], PHP_VERSION));
    }
}


//检测open_basedir
if (ini_get('open_basedir') != '') {
    json1(200, 0, '需要关闭open_basedir！如果已经关闭未生效，请重启php！');
}

//检测禁用函数
$require_functions = ['shell_exec', 'passthru'];
$disable_functions = explode(',', ini_get('disable_functions'));
foreach ($disable_functions as $disable) {
    if (in_array(trim($disable), $require_functions)) {
        json1(200, 0, "需要的 $disable 函数被禁用");
    }
}

//检测守护进程状态
$state = funDetectState();
if ($state == 0) {
    json1(401, 0, '守护进程响应超时');
} else if ($state == 2) {
    json1(401, 0, '后台程序未启动');
}

/**
 * 检查控制器和方法是否存在并实例化
 */
if (file_exists($controller_path)) {
    $obj = new $controller();
    if (is_callable(array($obj, $action))) {
        $obj->$action();
    } else {
        json1(401, 0, '无效的方法名');
    }
} else {
    json1(401, 0, '无效的控制器名');
}
