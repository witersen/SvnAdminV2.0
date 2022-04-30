<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-30 19:44:21
 * @Description: QQ:1801168257
 */

/*
 * 控制器基类，所有的控制器都要继承此类
 */

//require config
require_once BASE_PATH . '/config/curl.config.php';
require_once BASE_PATH . '/config/daemon.config.php';
require_once BASE_PATH . '/config/subversion.config.php';
require_once BASE_PATH . '/config/database.config.php';
require_once BASE_PATH . '/config/reg.config.php';
require_once BASE_PATH . '/config/router.config.php';
require_once BASE_PATH . '/config/sign.config.php';
require_once BASE_PATH . '/config/update.config.php';
require_once BASE_PATH . '/config/version.config.php';

//require model
require_once BASE_PATH . '/app/model/conn.model.php';

//require function
require_once BASE_PATH . '/app/function/array.function.php';
require_once BASE_PATH . '/app/function/color.function.php';
require_once BASE_PATH . '/app/function/curl.function.php';
require_once BASE_PATH . '/app/function/file.function.php';
require_once BASE_PATH . '/app/function/json.function.php';
require_once BASE_PATH . '/app/function/regular.function.php';
require_once BASE_PATH . '/app/function/return.function.php';
require_once BASE_PATH . '/app/function/socket.function.php';
require_once BASE_PATH . '/app/function/string.function.php';
require_once BASE_PATH . '/app/function/token.function.php';
require_once BASE_PATH . '/app/function/update.function.php';

//require controller
require_once BASE_PATH . '/app/controller/blacktoken.class.php';
require_once BASE_PATH . '/app/controller/common.class.php';
require_once BASE_PATH . '/app/controller/logs.class.php';
require_once BASE_PATH . '/app/controller/mail.class.php';
require_once BASE_PATH . '/app/controller/personal.class.php';
require_once BASE_PATH . '/app/controller/safe.class.php';
require_once BASE_PATH . '/app/controller/statistics.class.php';
require_once BASE_PATH . '/app/controller/subversion.class.php';
require_once BASE_PATH . '/app/controller/svnadmin.class.php';
require_once BASE_PATH . '/app/controller/svngroup.class.php';
require_once BASE_PATH . '/app/controller/svnrep.class.php';
require_once BASE_PATH . '/app/controller/svnuser.class.php';
require_once BASE_PATH . '/app/controller/update.class.php';

//require extension

//require svnadmin
require_once BASE_PATH . '/extension/SVNAdmin/src/core/Core.class.php';
require_once BASE_PATH . '/extension/SVNAdmin/src/class/SVNGroup.class.php';
require_once BASE_PATH . '/extension/SVNAdmin/src/class/SVNHooks.class.php';
require_once BASE_PATH . '/extension/SVNAdmin/src/class/SVNInfo.class.php';
require_once BASE_PATH . '/extension/SVNAdmin/src/class/SVNRep.class.php';
require_once BASE_PATH . '/extension/SVNAdmin/src/class/SVNUser.class.php';


class controller
{

    public $globalUserName;
    public $globalUserRoleId;

    public $token;

    public $globalAuthzContent;
    public $globalPasswdContent;

    public $requestPayload;

    public $database;

    //SVNAdmin对象
    public $SVNAdminGroup;
    public $SVNAdminHooks;
    public $SVNAdminInfo;
    public $SVNAdminRep;
    public $SVNAdminUser;

    function __construct()
    {
        /**
         * token
         */
        global $token;
        $this->token = $token;

        /**
         * 用户身份
         */
        $this->globalUserName = $this->GetUserNameByToken();
        $this->globalUserRoleId = $this->GetUserRoleByToken();

        /**
         * SVN配置文件相关
         */
        $this->globalAuthzContent = file_exists(SVN_AUTHZ_FILE) ? file_get_contents(SVN_AUTHZ_FILE) : '';
        $this->globalPasswdContent = file_exists(SVN_PASSWD_FILE) ? file_get_contents(SVN_PASSWD_FILE) : '';

        /**
         * 请求载体
         */
        global $requestPayload;
        $this->requestPayload = $requestPayload;

        /**
         * 数据库连接
         */
        $this->database = (new conn())->GetConn();

        /**
         * svnadmin对象
         */
        $this->SVNAdminGroup = new \SVNAdmin\SVN\Group($this->globalAuthzContent, $this->globalPasswdContent);
        $this->SVNAdminHooks = new \SVNAdmin\SVN\Hooks($this->globalAuthzContent, $this->globalPasswdContent);
        $this->SVNAdminInfo = new \SVNAdmin\SVN\Info($this->globalAuthzContent, $this->globalPasswdContent);
        $this->SVNAdminRep = new \SVNAdmin\SVN\Rep($this->globalAuthzContent, $this->globalPasswdContent);
        $this->SVNAdminUser = new \SVNAdmin\SVN\User($this->globalAuthzContent, $this->globalPasswdContent);
    }

    /**
     * 根据token获取用户角色id
     */
    final function GetUserRoleByToken()
    {
        if (!isset($this->token) || empty($this->token)) {
            return '';
        }
        $arr = explode('.', $this->token);
        return $arr[0];
    }

    /**
     * 根据token获取用户名称
     */
    final function GetUserNameByToken()
    {
        if (!isset($this->token) || empty($this->token)) {
            return '';
        }
        $arr = explode('.', $this->token);
        return $arr[1];
    }

    /**
     * 重新从authz文件中读取内容
     * 
     * 由于有些操作会更改authz文件内容且其它操作依赖这一实时结果 因此需要及时更新
     */
    final function UPdateAuthz()
    {
        $this->globalAuthzContent = file_exists(SVN_AUTHZ_FILE) ? file_get_contents(SVN_AUTHZ_FILE) : '';
    }
}
