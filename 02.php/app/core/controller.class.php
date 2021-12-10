<?php

/*
 * 控制器基类，所有的控制器都要继承此类
 */

//require model
require_once BASE_PATH . '/app/model/connModel.class.php';

//require controller
require_once BASE_PATH . '/app/controller/client.class.php';
require_once BASE_PATH . '/app/controller/config.class.php';
require_once BASE_PATH . '/app/controller/crontab.class.php';
require_once BASE_PATH . '/app/controller/firewall.class.php';
require_once BASE_PATH . '/app/controller/mail.class.php';
require_once BASE_PATH . '/app/controller/svnserve.class.php';
require_once BASE_PATH . '/app/controller/system.class.php';
require_once BASE_PATH . '/app/controller/user.class.php';

//require function
require_once BASE_PATH . '/app/function/token.function.php';
require_once BASE_PATH . '/app/function/socket.function.php';

class Controller
{

    public $database_medoo;
    public $this_userid;
    public $this_username;

    function __construct()
    {
        $this->database_medoo = (new connModel())->GetConn();
        $this->this_userid = $this->GetUserInfoByToken(MY_TOKEN)["userid"];
        $this->this_username = $this->GetUserInfoByToken(MY_TOKEN)["username"];
    }

    //根据token获取userid
    final function GetUserInfoByToken($token)
    {
        $explode = explode('.', $token);
        $result = $this->database_medoo->select("user", ["username"], ["id" => $explode[0]]);
        $data = array(
            "userid" => $explode[0],
            "username" => $result[0]["username"]
        );
        return $data;
    }
}
