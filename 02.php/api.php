<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

/**
 * PHP 5.5+
 */

use Medoo\Medoo;

/**
 * 开启错误信息 如需要调试 可取消注释
 */
// ini_set('display_errors', '1');
// error_reporting(E_ALL);

set_time_limit(0);

ini_set('pcre.backtrack_limit', -1);

ini_set('pcre.recursion_limit', -1);

define('BASE_PATH', __DIR__);

define('IPC_SVNADMIN', BASE_PATH . '/server/svnadmind.socket');

date_default_timezone_set('PRC');

require_once BASE_PATH . '/app/controller/base/Base.php';

Config::load(BASE_PATH . '/config/');

$token = '';
if (isset($_SERVER['HTTP_TOKEN'])) {
    $token = urldecode($_SERVER['HTTP_TOKEN']);
}

$controller_prefix = empty($_GET['c']) ? '' : $_GET['c'];
!strstr($controller_prefix, '/') or json1(401, 0, '包含特殊字符');
$controller = "\app\controller\\$controller_prefix";
$controller_path = BASE_PATH . '/app/controller/' . $controller_prefix . '.php';

$action = empty($_GET['a']) ? '' : $_GET['a'];
!strstr($action, '/') or json1(401, 0, '包含特殊字符');

$type = empty($_GET['t']) ? '' : $_GET['t'];
!strstr($type, '/') or json1(401, 0, '包含特殊字符');

$payload = file_get_contents("php://input");
$payload = !empty($payload) ? json_decode($payload, true) : [];

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

if (ini_get('open_basedir') != '') {
    json1(200, 0, '需要关闭open_basedir！如果已经关闭未生效，请重启php！');
}

$require_functions = ['shell_exec', 'passthru'];
$disable_functions = explode(',', ini_get('disable_functions'));
foreach ($disable_functions as $disable) {
    if (in_array(trim($disable), $require_functions)) {
        json1(200, 0, "需要的 $disable 函数被禁用");
    }
}

$state = funDetectState();
if ($state == 0) {
    json1(401, 0, '守护进程响应超时');
} elseif ($state == 2) {
    json1(401, 0, '后台程序未启动');
}

$configDatabase = Config::get('database');
$configSvn = Config::get('svn');
if (array_key_exists('database_file', $configDatabase)) {
    $configDatabase['database_file'] = sprintf($configDatabase['database_file'], $configSvn['home_path']);
}
try {
    $database = new Medoo($configDatabase);
} catch (\Exception $e) {
    json1(200, 0, $e->getMessage());
}

if (file_exists($controller_path)) {
    $obj = new $controller($parm = [
        'token' => $token,
        'payload' => $payload,
        'type' => $type,
        'controller_prefix' => $controller_prefix,
        'action' => $action
    ]);
    if (is_callable(array($obj, $action))) {
        $obj->$action();
    } else {
        json1(401, 0, '无效的方法名');
    }
} else {
    json1(401, 0, '无效的控制器名');
}
