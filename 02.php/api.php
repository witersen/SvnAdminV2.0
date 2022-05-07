<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 11:17:34
 * @Description: QQ:1801168257
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

/**
 * 获取文件信息
 * 适用请求方式 fordata
 * Content-Type: multipart/form-data;
 * 
 * 示例数据
 * 其中 file 为前端请求的自定义字段 使用时候要自行判断是否存在该键值
 * {
 * 	"file": {
 * 		"name": "tmp_bda9c778201ffb47ebfea61617a16d1c564ca6d0b8ad52b8.jpg",
 * 		"type": "image\/jpeg",
 * 		"tmp_name": "\/tmp\/phpwxfAaU",
 * 		"error": 0,
 * 		"size": 166881
 * 	}
 * }
 */

//检测守护进程状态
$state = FunDetectState();
if ($state == 0) {
    json1(401, 0, '守护进程响应超时');
} else if ($state == 2) {
    json1(401, 0, '守护进程未启动');
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
