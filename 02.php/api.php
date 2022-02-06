<?php

declare(strict_types=1);

/**
 * 开启错误信息 如需要调试 可取消注释
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type:application/json; charset=utf-8');

define('BASE_PATH', __DIR__);

date_default_timezone_set('PRC');

require_once BASE_PATH . '/app/core/controller.class.php';

/**
 * token
 */
define('MY_TOKEN', empty($_SERVER['HTTP_TOKEN']) ? '' : $_SERVER['HTTP_TOKEN']);

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
define('MY_FUNCTION', $action);

/**
 * 参数
 */
$requestPayload = file_get_contents("php://input");
$requestPayload = !empty($requestPayload) ? json_decode($requestPayload, true) : [];

/**
 * 检查控制器和方法是否存在并实例化
 */
if (file_exists($controller_path)) {
    $controller = new $controller_perifx();
    if (is_callable(array($controller, $action))) {
        //检测守护进程是否开启
        $state = FunDetectState();
        if ($state == 0) {
            $data['status'] = 0;
            $data['code'] = 501;
            $data['message'] = '后台程序响应超时';
            echo json_encode($data);
            return;
        } else if ($state == 2) {
            $data['status'] = 0;
            $data['code'] = 501;
            $data['message'] = '后台程序未启动';
            echo json_encode($data);
            return;
        }
        //检测token
        if (MY_FUNCTION != 'Login') {
            $data = FunCheckToken(MY_TOKEN);
            if ($data['code'] != '200') {
                $result = array(
                    'status' => '0',
                    'code' => $data['code'],
                    'message' => $data['message']
                );
                echo json_encode($result);
                return;
            }
        }
        //执行请求
        echo json_encode($controller->$action($requestPayload));
    }
}
