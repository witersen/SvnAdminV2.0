<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-26 17:01:57
 * @Description: QQ:1801168257
 */

/**
 * 开启错误信息 如需要调试 可取消注释
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

define('BASE_PATH', __DIR__);

date_default_timezone_set('PRC');

require_once BASE_PATH . '/app/core/controller.class.php';

/**
 * token
 */
$token = empty($_SERVER['HTTP_TOKEN']) ? '' : $_SERVER['HTTP_TOKEN'];

/**
 * 控制器
 */
$controller_perifx = empty($_GET['c']) ? '' : $_GET['c']; //控制器前缀
$controller_name = $controller_perifx . '.class'; //控制器名称
$controller_path = BASE_PATH . '/app/controller/' . $controller_name . '.php'; //控制器路径

/**
 * 方法
 */
$action = empty($_GET['a']) ? '' : $_GET['a'];

/**
 * 接口类型
 * 小程序还是web系统
 */
$type = isset($_GET['t']) ? $_GET['t'] : '';

/**
 * 请求参数即Request Payload
 * Content-Type: application/json
 */
$requestPayload = file_get_contents("php://input");
$requestPayload = !empty($requestPayload) ? json_decode($requestPayload, true) : [];

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
// $files = $_FILES;

/**
 * 检查控制器和方法是否存在并实例化
 */
if (file_exists($controller_path)) {
    //检测守护进程状态
    $state = FunDetectState();
    if ($state == 0) {
        FunMessageExit(401, 0, '守护进程响应超时');
    } else if ($state == 2) {
        FunMessageExit(401, 0, '守护进程未启动');
    }

    //白名单检查
    if ($type == 'mini') {
        if (!in_array($controller_perifx . '/' . $action, $ROUTERS['MINI_PUBLIC_ROUTERS'])) {
            FunCheckToken($token);
        }
    } else if ($type == 'web') {
        if (!in_array($controller_perifx . '/' . $action, $ROUTERS['WEB_PUBLIC_ROUTERS'])) {
            FunCheckToken($token);
        }
    } else {
        FunMessageExit(401, 0, '无效的接口类型');
    }

    //检查token是否已注销
    (new blacktoken())->CheckBlack();

    //开始调用
    $controller = new $controller_perifx();
    if (is_callable(array($controller, $action))) {
        //检查SVN用户的路由表
        if ($controller->globalUserRoleId == 2) {
            if (!in_array($controller_perifx . '/' . $action, $ROUTERS['SVN_USER_ROUTERS'])) {
                FunMessageExit(401, 0, '无权限');
            }
        }
        //执行请求
        $controller->$action();
    } else {
        FunMessageExit(401, 0, '无效的方法名');
    }
} else {
    FunMessageExit(401, 0, '无效的控制器名');
}
