<?php
/*
 * @Author: witersen
 * @Date: 2022-05-06 18:41:32
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 13:15:21
 * @Description: QQ:1801168257
 */

namespace app\controller;

//require config
require_once BASE_PATH . '/config/daemon.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/reg.php';
require_once BASE_PATH . '/config/router.php';
require_once BASE_PATH . '/config/sign.php';
require_once BASE_PATH . '/config/svn.php';
require_once BASE_PATH . '/config/update.php';
require_once BASE_PATH . '/config/version.php';

//require model

//require function
require_once BASE_PATH . '/app/function/array.php';
require_once BASE_PATH . '/app/function/color.php';
require_once BASE_PATH . '/app/function/curl.php';
require_once BASE_PATH . '/app/function/file.php';
require_once BASE_PATH . '/app/function/ipc.php';
require_once BASE_PATH . '/app/function/json.php';
require_once BASE_PATH . '/app/function/message.php';
require_once BASE_PATH . '/app/function/string.php';
require_once BASE_PATH . '/app/function/update.php';

//require util
require_once BASE_PATH . '/app/util/SVNAdmin/Core.php';
require_once BASE_PATH . '/app/util/SVNAdmin/Group.php';
require_once BASE_PATH . '/app/util/SVNAdmin/Rep.php';
require_once BASE_PATH . '/app/util/SVNAdmin/User.php';

require_once BASE_PATH . '/app/util/Check.php';
require_once BASE_PATH . '/app/util/Config.php';

//require controller
require_once BASE_PATH . '/app/controller/Common.php';
require_once BASE_PATH . '/app/controller/Logs.php';
require_once BASE_PATH . '/app/controller/Mail.php';
require_once BASE_PATH . '/app/controller/Personal.php';
require_once BASE_PATH . '/app/controller/Safe.php';
require_once BASE_PATH . '/app/controller/Statistics.php';
require_once BASE_PATH . '/app/controller/Svn.php';
require_once BASE_PATH . '/app/controller/Svnaliase.php';
require_once BASE_PATH . '/app/controller/Svngroup.php';
require_once BASE_PATH . '/app/controller/Svnrep.php';
require_once BASE_PATH . '/app/controller/Svnuser.php';
require_once BASE_PATH . '/app/controller/Update.php';

//require service
require_once BASE_PATH . '/app/service/base/Base.php';
require_once BASE_PATH . '/app/service/Common.php';
require_once BASE_PATH . '/app/service/Logs.php';
require_once BASE_PATH . '/app/service/Mail.php';
require_once BASE_PATH . '/app/service/Personal.php';
require_once BASE_PATH . '/app/service/Safe.php';
require_once BASE_PATH . '/app/service/Statistics.php';
require_once BASE_PATH . '/app/service/Svn.php';
require_once BASE_PATH . '/app/service/Svnaliase.php';
require_once BASE_PATH . '/app/service/Svngroup.php';
require_once BASE_PATH . '/app/service/Svnrep.php';
require_once BASE_PATH . '/app/service/Svnuser.php';
require_once BASE_PATH . '/app/service/Update.php';

//require extension
require_once BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php';

require_once BASE_PATH . '/extension/PHPMailer-6.6.0/src/Exception.php';
require_once BASE_PATH . '/extension/PHPMailer-6.6.0/src/PHPMailer.php';
require_once BASE_PATH . '/extension/PHPMailer-6.6.0/src/SMTP.php';
require_once BASE_PATH . '/extension/PHPMailer-6.6.0/language/phpmailer.lang-zh_cn.php';

require_once BASE_PATH . '/extension/Verifycode/Verifycode.php';

require_once BASE_PATH . '/extension/Witersen/SVNAdmin.php';

class Base
{
    function __construct()
    {
    }
}
