<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Setting as ServiceSetting;
use app\service\Mail as ServiceMail;
use app\service\Svnrep as ServiceSvnrep;

class Setting extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSetting;
    private $ServiceMail;
    private $ServiceSvnrep;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceSetting = new ServiceSetting($parm);
        $this->ServiceMail = new ServiceMail($parm);
        $this->ServiceSvnrep = new ServiceSvnrep($parm);
    }

    /**
     * 获取Subversion的详细信息
     */
    public function GetSvnserveInfo()
    {
        $result = $this->ServiceSvnrep->GetSvnserveInfo();
        json2($result);
    }

    /**
     * 启动SVN
     */
    public function StartSvnserve()
    {
        $result = $this->ServiceSetting->StartSvnserve();
        json2($result);
    }

    /**
     * 停止SVN
     */
    public function StopSvnserve()
    {
        $result = $this->ServiceSetting->StopSvnserve();
        json2($result);
    }

    /**
     * 修改svnserve的绑定端口
     */
    public function UpdSvnservePort()
    {
        $result = $this->ServiceSetting->UpdSvnservePort();
        json2($result);
    }

    /**
     * 修改svnserve的绑定主机
     */
    public function UpdSvnserveHost()
    {
        $result = $this->ServiceSetting->UpdSvnserveHost();
        json2($result);
    }

    /**
     * 修改管理系统主机名
     */
    public function UpdManageHost()
    {
        $result = $this->ServiceSetting->UpdManageHost();
        json2($result);
    }

    /**
     * 修改检出地址
     */
    public function UpdCheckoutHost()
    {
        $result = $this->ServiceSetting->UpdCheckoutHost();
        json2($result);
    }

    /**
     * 获取配置文件列表
     */
    public function GetDirInfo()
    {
        $result = $this->ServiceSetting->GetDirInfo();
        json2($result);
    }

    /**
     * 检测新版本
     */
    public function CheckUpdate()
    {
        $result = $this->ServiceSetting->CheckUpdate();
        json2($result);
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
     * 发送测试邮件
     */
    public function SendMailTest()
    {
        $result = $this->ServiceMail->SendMailTest();
        json2($result);
    }

    /**
     * 修改邮件配置信息
     */
    public function UpdMailInfo()
    {
        $this->ServiceMail->UpdMailInfo();
        json2();
    }

    /**
     * 获取消息推送信息配置
     */
    public function GetMailPushInfo()
    {
        $result = $this->ServiceMail->GetPushInfo();
        json2($result);
    }

    /**
     * 获取安全配置选项
     *
     * @return array
     */
    public function GetSafeInfo()
    {
        $result = $this->ServiceSetting->GetSafeInfo();
        json2($result);
    }

    /**
     * 设置安全配置选项
     *
     * @return array
     */
    public function UpdSafeInfo()
    {
        $result = $this->ServiceSetting->UpdSafeInfo();
        json2($result);
    }

    /**
     * 修改推送选项
     */
    function UpdPushInfo()
    {
        $result = $this->ServiceMail->UpdPushInfo();
        json2($result);
    }

    /**
     * 获取登录验证码选项
     *
     * @return array
     */
    public function GetVerifyOption()
    {
        $result = $this->ServiceSetting->GetVerifyOption();
        json2($result);
    }
}
