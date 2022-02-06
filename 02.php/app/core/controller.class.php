<?php

declare(strict_types=1);

/*
 * 控制器基类，所有的控制器都要继承此类
 */

//require model
// require_once BASE_PATH . '/app/model/connModel.class.php';

require_once BASE_PATH . '/config/auto.config.php';
require_once BASE_PATH . '/config/manual.config.php';
require_once BASE_PATH . '/config/reg.config.php';

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
require_once BASE_PATH . '/app/function/array.function.php';
require_once BASE_PATH . '/app/function/config.function.php';
require_once BASE_PATH . '/app/function/curl.function.php';
require_once BASE_PATH . '/app/function/file.function.php';
require_once BASE_PATH . '/app/function/regular.function.php';
require_once BASE_PATH . '/app/function/socket.function.php';
require_once BASE_PATH . '/app/function/string.function.php';
require_once BASE_PATH . '/app/function/svnGroup.function.php';
require_once BASE_PATH . '/app/function/svnHooks.function.php';
require_once BASE_PATH . '/app/function/svnRep.function.php';
require_once BASE_PATH . '/app/function/svnUser.function.php';
require_once BASE_PATH . '/app/function/token.function.php';
require_once BASE_PATH . '/app/function/update.function.php';


class Controller
{

    public $globalUserName;
    public $globalUserRoleId;
    public $globalAuthzContent;
    public $globalPasswdContent;

    function __construct()
    {
        $this->globalUserName = $this->GetUserInfoByToken(MY_TOKEN)["username"];
        $this->globalUserRoleId = $this->globalUserName == MANAGE_USER ? 1 : 2;
        $this->globalAuthzContent = file_exists(SVN_SERVER_AUTHZ) ? file_get_contents(SVN_SERVER_AUTHZ) : '';
        $this->globalPasswdContent = file_exists(SVN_SERVER_PASSWD) ? file_get_contents(SVN_SERVER_PASSWD) : '';
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
