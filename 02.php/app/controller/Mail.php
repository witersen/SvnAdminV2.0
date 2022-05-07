<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 13:58:27
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Mail as ServiceMail;

class Mail extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceMail;

    function __construct()
    {
        parent::__construct();

        $this->ServiceMail = new ServiceMail();
    }

    /**
     * 获取邮件配置信息
     */
    public function GetEmail()
    {
        $result = $this->ServiceMail->GetEmail();
        json2($result);
    }

    /**
     * 修改邮件配置信息
     */
    public function EditEmail()
    {
        $this->ServiceMail->EditEmail();
        json2();
    }

    /**
     * 发送测试邮件
     */
    public function SendTest()
    {
        $result = $this->ServiceMail->SendTest();
        json2($result);
    }
}
