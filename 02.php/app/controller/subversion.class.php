<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-30 02:32:15
 * @Description: QQ:1801168257
 */

class subversion extends controller
{
    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
    }

    /**
     * 获取Subversion运行状态 用于页头提醒
     */
    function GetStatus()
    {
        $result = FunShellExec("ps auxf | grep -v 'grep' | grep svnserve");

        if ($result == '') {
            FunMessageExit(200, 0, 'svnserve服务未在运行，SVN用户将无法使用仓库浏览功能');
        } else {
            FunMessageExit();
        }
    }

    /**
     * 获取Subversion的检出地址前缀
     * 
     * 先从Subversion配置文件获取绑定端口和主机
     * 然后与listen.json配置文件中的端口和主机进行对比和同步
     */
    function GetCheckout()
    {
        $result = $this->SVNAdminInfo->GetSubversionListen(SVNSERVE_ENV_FILE, LISTEN_FILE);
        $checkoutHost = $result[$result['enable']];
        if ($result['bindPort'] != '3690') {
            $checkoutHost .= ':' . $result['bindPort'];
        }
        FunMessageExit(200, 1, '成功', [
            'protocal' => 'svn://',
            'prefix' => $checkoutHost
        ]);
    }

    /**
     * 获取Subversion的详细信息
     */
    function GetDetail()
    {
        //获取绑定主机、端口等信息
        $bindInfo = $this->SVNAdminInfo->GetSubversionListen(SVNSERVE_ENV_FILE, LISTEN_FILE);

        //检测运行信息
        $runInfo = FunShellExec('ps auxf|grep -v "grep"|grep svnserve');

        //检测安装信息
        $installInfo = file_exists('/usr/bin/svnserve');

        //检测安装状态
        //未知状态
        $installInfo = -1;
        if ($runInfo == '' && !$installInfo) {
            //未安装
            $installed = 0;
        } else if ($runInfo == '' && $installInfo) {
            //安装未启动
            $installed = 1;
        } else if ($runInfo != '' && $installInfo) {
            //运行中
            $installed = 2;
        }

        //检测subversion版本
        $version = '-';
        if ($installed != 0) {
            $versionInfo = FunShellExec('svnserve --version');
            preg_match_all(REG_SUBVERSION_VERSION, $versionInfo, $versionInfoPreg);
            if (array_key_exists(0, $versionInfoPreg[0])) {
                $version = trim($versionInfoPreg[1][0]);
            } else {
                $version = '-';
            }
        }

        FunMessageExit(200, 1, '成功', [
            'version' => $version,
            'installed' => $installed,
            'bindPort' => $bindInfo['bindPort'],
            'bindHost' => $bindInfo['bindHost'],
            'manageHost' => $bindInfo['manageHost'],
            'enable' => $bindInfo[$bindInfo['enable']],
            'svnserveLog' => SVNSERVE_LOG_FILE
        ]);
    }

    /**
     * 安装SVN
     */
    function Install()
    {
    }

    /**
     * 卸载SVN
     */
    function UnInstall()
    {
    }

    /**
     * 启动SVN
     */
    function Start()
    {
        FunShellExec("systemctl start svnserve");
        FunMessageExit();
    }

    /**
     * 停止SVN
     */
    function Stop()
    {
        FunShellExec("systemctl stop svnserve");
        FunMessageExit();
    }

    /**
     * 修改svnserve的绑定端口
     */
    function EditPort()
    {
    }

    /**
     * 修改svnserve的绑定主机
     */
    function EditHost()
    {
    }

    /**
     * 修改管理系统主机名
     */
    function EditManageHost()
    {
    }

    /**
     * 修改检出地址
     */
    function EditEnable()
    {
    }

    /**
     * 查看svnserve运行日志
     */
    function ViewSvnserveLog()
    {
    }

    /**
     * 获取配置文件列表
     */
    function GetConfig()
    {
        FunMessageExit(200, 1, '成功', [
            [
                'key' => 'SVN仓库父目录',
                'value' => SVN_REPOSITORY_PATH
            ],
            [
                'key' => 'svnserve环境变量文件',
                'value' => SVNSERVE_ENV_FILE
            ],
            [
                'key' => 'SVN仓库权限配置文件',
                'value' => SVN_CONF_FILE
            ],
            [
                'key' => 'authz文件',
                'value' => SVN_AUTHZ_FILE
            ],
            [
                'key' => 'passwd文件',
                'value' => SVN_PASSWD_FILE
            ],
            [
                'key' => '备份文件夹',
                'value' => SVN_BACHUP_PATH
            ],
        ]);
    }
}
