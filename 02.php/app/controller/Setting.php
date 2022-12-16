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
use app\service\Ldap as ServiceLdap;
use app\service\Svn as ServiceSvn;
use app\service\Apache as ServiceApache;

class Setting extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSetting;
    private $ServiceMail;
    private $ServiceLdap;
    private $ServiceSvn;
    private $ServiceApache;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceSetting = new ServiceSetting($parm);
        $this->ServiceMail = new ServiceMail($parm);
        $this->ServiceLdap = new ServiceLdap($parm);
        $this->ServiceSvn = new ServiceSvn($parm);
        $this->ServiceApache = new ServiceApache($parm);
    }

    /**
     * 获取宿主机配置
     *
     * @return array
     */
    public function GetDcokerHostInfo()
    {
        $result = $this->ServiceSetting->GetDcokerHostInfo();
        json2($result);
    }

    /**
     * 修改宿主机配置
     */
    public function UpdDockerHostInfo()
    {
        $result = $this->ServiceSetting->UpdDockerHostInfo();
        json2($result);
    }

    /**
     * 获取 svnserve 的详细信息
     */
    public function GetSvnInfo()
    {
        $result = $this->ServiceSvn->GetSvnInfo();
        json2($result);
    }

    /**
     * 保存 svnserve 相关配置
     */
    public function UpdSvnUsersource()
    {
        $result = $this->ServiceSvn->UpdSvnUsersource();
        json2($result);
    }

    /**
     * 启动SVN
     */
    public function UpdSvnserveStatusStart()
    {
        $result = $this->ServiceSvn->UpdSvnserveStatusStart();
        json2($result);
    }

    /**
     * 停止SVN
     */
    public function UpdSvnserveStatusStop()
    {
        $result = $this->ServiceSvn->UpdSvnserveStatusStop();
        json2($result);
    }

    /**
     * 修改 svnserve 监听端口
     */
    public function UpdSvnservePort()
    {
        $result = $this->ServiceSetting->UpdSvnservePort();
        json2($result);
    }

    /**
     * 修改 svnserve 的监听主机
     */
    public function UpdSvnserveHost()
    {
        $result = $this->ServiceSetting->UpdSvnserveHost();
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

    /**
     * 测试连接ldap服务器
     *
     * @return array
     */
    public function LdapTest()
    {
        $result = $this->ServiceLdap->LdapTest();
        json2($result);
    }

    /**
     * 开启 saslauthd 服务
     *
     * @return void
     */
    public function UpdSaslStatusStart()
    {
        $result = $this->ServiceSvn->UpdSaslStatusStart();
        json2($result);
    }

    /**
     * 关闭 saslauthd 服务
     *
     * @return void
     */
    public function UpdSaslStatusStop()
    {
        $result = $this->ServiceSvn->UpdSaslStatusStop();
        json2($result);
    }

    /**
     * 获取 apache 服务器信息
     *
     * @return void
     */
    public function GetApacheInfo()
    {
        $result = $this->ServiceApache->GetApacheInfo();
        json2($result);
    }

    /**
     * 启用 http 协议检出
     *
     * @return void
     */
    public function UpdSubversionEnable()
    {
        $result = $this->ServiceApache->UpdSubversionEnable();
        json2($result);
    }

    /**
     * 启用 svn 协议检出
     *
     * @return void
     */
    public function UpdSvnEnable()
    {
        $result = $this->ServiceSvn->UpdSvnEnable();
        json2($result);
    }

    /**
     * 修改 http 协议访问前缀
     *
     * @return void
     */
    public function UpdHttpPrefix()
    {
        $result = $this->ServiceApache->UpdHttpPrefix();
        json2($result);
    }

    /**
     * 修改 http 协议显示端口
     *
     * @return void
     */
    public function UpdHttpPort()
    {
        $result = $this->ServiceApache->UpdHttpPort();
        json2($result);
    }

    /**
     * 保存 apache 相关配置
     */
    public function UpdHttpUsersource()
    {
        $result = $this->ServiceApache->UpdHttpUsersource();
        json2($result);
    }
}
