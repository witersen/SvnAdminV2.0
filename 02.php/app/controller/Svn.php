<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 14:02:42
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Svn as ServiceSvn;

class Svn extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSvn;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceSvn = new ServiceSvn($parm);
    }

    /**
     * 获取Subversion运行状态 用于页头提醒
     */
    public function GetStatus()
    {
        $result = $this->ServiceSvn->GetStatus();
        json2($result);
    }

    /**
     * 获取Subversion的检出地址前缀
     * 
     * 先从Subversion配置文件获取绑定端口和主机
     * 然后与listen.json配置文件中的端口和主机进行对比和同步
     */
    public function GetCheckout()
    {
        $result = $this->ServiceSvn->GetCheckout();
        json2($result);
    }

    /**
     * 获取Subversion的详细信息
     */
    public function GetDetail()
    {
        $result = $this->ServiceSvn->GetDetail();
        json2($result);
    }

    /**
     * 安装SVN
     */
    public function Install()
    {
        $result = $this->ServiceSvn->Install();
        json2($result);
    }

    /**
     * 卸载SVN
     */
    public function UnInstall()
    {
        $result = $this->ServiceSvn->UnInstall();
        json2($result);
    }

    /**
     * 启动SVN
     */
    public function Start()
    {
        $result = $this->ServiceSvn->Start();
        json2($result);
    }

    /**
     * 停止SVN
     */
    public function Stop()
    {
        $result = $this->ServiceSvn->Stop();
        json2($result);
    }

    /**
     * 修改svnserve的绑定端口
     */
    public function EditPort()
    {
        $result = $this->ServiceSvn->EditPort();
        json2($result);
    }

    /**
     * 修改svnserve的绑定主机
     */
    public function EditHost()
    {
        $result = $this->ServiceSvn->EditHost();
        json2($result);
    }

    /**
     * 修改管理系统主机名
     */
    public function EditManageHost()
    {
        $result = $this->ServiceSvn->EditManageHost();
        json2($result);
    }

    /**
     * 修改检出地址
     */
    public function EditEnable()
    {
        $result = $this->ServiceSvn->EditEnable();
        json2($result);
    }

    /**
     * 获取配置文件列表
     */
    public function GetConfig()
    {
        $result = $this->ServiceSvn->GetConfig();
        json2($result);
    }

    /**
     * 检测 authz 是否有效
     *
     * @return array
     */
    public function ValidateAuthz()
    {
        $result = $this->ServiceSvn->ValidateAuthz();
        json2($result);
    }
}
