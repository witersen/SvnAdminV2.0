<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Common as ServiceCommon;

class Common extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceCommon;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceCommon = new ServiceCommon($parm);
    }

    /**
     * 登录
     */
    public function Login()
    {
        $result = $this->ServiceCommon->Login();
        json2($result);
    }

    /**
     * 注销
     * 
     * 注销操作为将用户尚未过期的token加入所谓黑名单
     * 每次注销触发主动扫描黑名单 将名单中过期的token删除
     * 目的：实现用户注销后尚未过期的token无法继续使用
     */
    public function Logout()
    {
        $result = $this->ServiceCommon->Logout();
        json2($result);
    }

    /**
     * 获取验证码
     */
    public function GetVerifyCode()
    {
        $result = $this->ServiceCommon->GetVerifyCode();
        json2($result);
    }
}
