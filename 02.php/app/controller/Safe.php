<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-06 21:37:21
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Safe as ServiceSafe;

class Safe extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSafe;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceSafe = new ServiceSafe($parm);
    }

    /**
     * 获取安全配置选项
     *
     * @return array
     */
    public function GetSafeInfo()
    {
        $result = $this->ServiceSafe->GetSafeInfo();
        json2($result);
    }

    /**
     * 设置安全配置选项
     *
     * @return array
     */
    public function UpdSafeConfig()
    {
        $result = $this->ServiceSafe->UpdSafeConfig();
        json2($result);
    }

    /**
     * 获取登录验证码选项
     *
     * @return array
     */
    public function GetVerifyOption()
    {
        $result = $this->ServiceSafe->GetVerifyOption();
        json2($result);
    }
}
