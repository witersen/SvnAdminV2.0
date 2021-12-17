<?php

/*
 * 控制器基类，所有的控制器都要继承此类
 */

//require model
// require_once BASE_PATH . '/app/model/connModel.class.php';

require_once BASE_PATH . '/config/auto.config.php';
require_once BASE_PATH . '/config/manual.config.php';

//require controller
require_once BASE_PATH . '/app/controller/client.class.php';
require_once BASE_PATH . '/app/controller/config.class.php';
require_once BASE_PATH . '/app/controller/crontab.class.php';
require_once BASE_PATH . '/app/controller/firewall.class.php';
require_once BASE_PATH . '/app/controller/mail.class.php';
require_once BASE_PATH . '/app/controller/svnserve.class.php';
require_once BASE_PATH . '/app/controller/system.class.php';
require_once BASE_PATH . '/app/controller/user.class.php';
require_once BASE_PATH . '/app/controller/update.class.php';

//require function
require_once BASE_PATH . '/app/function/authz.function.php';
require_once BASE_PATH . '/app/function/config.function.php';
require_once BASE_PATH . '/app/function/hooks.function.php';
require_once BASE_PATH . '/app/function/passwd.function.php';
require_once BASE_PATH . '/app/function/socket.function.php';
require_once BASE_PATH . '/app/function/token.function.php';
require_once BASE_PATH . '/app/function/file.function.php';
require_once BASE_PATH . '/app/function/svn.function.php';
require_once BASE_PATH . '/app/function/curl.function.php';
require_once BASE_PATH . '/app/function/update.function.php';
require_once BASE_PATH . '/app/function/public.function.php';

class Controller
{

    public $this_username;
    public $this_roleid;

    function __construct()
    {
        $this->this_username = $this->GetUserInfoByToken(MY_TOKEN)["username"];
        $this->this_roleid = $this->this_username == MANAGE_USER ? 1 : 2;
    }

    //根据token获取用户信息
    final function GetUserInfoByToken($token)
    {
        $explode = explode('.', $token);
        $data = array(
            "username" => $explode[0]
        );
        return $data;
    }
}
