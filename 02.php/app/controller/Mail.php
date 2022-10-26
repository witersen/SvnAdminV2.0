<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 19:14:22
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

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceMail = new ServiceMail($parm);
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

    /**
     * 获取消息推送信息配置
     */
    public function GetPush()
    {
        $result = $this->ServiceMail->GetPush();
        json2($result);
    }

    /**
     * 修改推送选项
     */
    function EditPush()
    {
        $result = $this->ServiceMail->EditPush();
        json2($result);
    }
}
