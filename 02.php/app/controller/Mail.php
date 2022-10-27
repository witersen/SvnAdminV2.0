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
    public function GetMailInfo()
    {
        $result = $this->ServiceMail->GetMailInfo();
        json2($result);
    }

    /**
     * 修改邮件配置信息
     */
    public function UpdMail()
    {
        $this->ServiceMail->UpdMail();
        json2();
    }

    /**
     * 发送测试邮件
     */
    public function TestMail()
    {
        $result = $this->ServiceMail->TestMail();
        json2($result);
    }

    /**
     * 获取消息推送信息配置
     */
    public function GetPushInfo()
    {
        $result = $this->ServiceMail->GetPushInfo();
        json2($result);
    }

    /**
     * 修改推送选项
     */
    function UpdPush()
    {
        $result = $this->ServiceMail->UpdPush();
        json2($result);
    }
}
