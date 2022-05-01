<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-02 00:55:54
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
        $result = $result['result'];

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
        if ($result['bindPort'] != 3690) {
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
        $runInfo = $runInfo['result'];

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
            $versionInfo = $versionInfo['result'];
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
            'bindPort' => (int)$bindInfo['bindPort'],
            'bindHost' => $bindInfo['bindHost'],
            'manageHost' => $bindInfo['manageHost'],
            'enable' => $bindInfo['enable'],
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
        //port不能为空

        //获取现在的端口与要修改的端口对比检查是否相同
        $result = $this->SVNAdminInfo->GetSubversionListen(SVNSERVE_ENV_FILE, LISTEN_FILE);

        if ($this->payload['bindPort'] == $result['bindPort']) {
            FunMessageExit(200, 0, '无需更换，端口相同');
        }

        //停止svnserve
        FunShellExec('systemctl stop svnserve');

        //重新构建配置文件内容
        $config = sprintf("OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"", SVN_REPOSITORY_PATH, SVN_CONF_FILE, SVNSERVE_LOG_FILE, $this->payload['bindPort'], $result['bindHost']);

        //写入配置文件
        FunShellExec('echo \'' . $config . '\' > ' . SVNSERVE_ENV_FILE);

        //启动svnserve
        $result = FunShellExec('systemctl start svnserve');

        if ($result['resultCode'] != 0) {
            FunMessageExit(200, 0, '启动异常' . $result['error']);
        } else {
            FunMessageExit();
        }
    }

    /**
     * 修改svnserve的绑定主机
     */
    function EditHost()
    {
        //host不能为空
        //不能带前缀如http或者https

        //获取现在的绑定主机与要修改的主机对比检查是否相同
        $result = $this->SVNAdminInfo->GetSubversionListen(SVNSERVE_ENV_FILE, LISTEN_FILE);

        if ($this->payload['bindHost'] == $result['bindHost']) {
            FunMessageExit(200, 0, '无需更换，地址相同');
        }

        //停止svnserve
        FunShellExec('systemctl stop svnserve');

        //重新构建配置文件内容
        $config = sprintf("OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"", SVN_REPOSITORY_PATH, SVN_CONF_FILE, SVNSERVE_LOG_FILE, $result['bindPort'], $this->payload['bindHost']);

        //写入配置文件
        FunShellExec('echo \'' . $config . '\' > ' . SVNSERVE_ENV_FILE);

        //启动svnserve
        $result = FunShellExec('systemctl start svnserve');

        if ($result['resultCode'] != 0) {
            FunMessageExit(200, 0, '启动异常' . $result['error']);
        } else {
            FunMessageExit();
        }
    }

    /**
     * 修改管理系统主机名
     */
    function EditManageHost()
    {
        //不能为空
        //不能带前缀如http或者https
        
        $result = $this->SVNAdminInfo->GetSubversionListen(SVNSERVE_ENV_FILE, LISTEN_FILE);

        if ($this->payload['manageHost'] == $result['manageHost']) {
            FunMessageExit(200, 0, '无需更换，地址相同');
        }

        //更新内容
        FunShellExec('echo \'' . json_encode([
            'bindPort' => $result['bindPort'],
            'bindHost' => $result['bindHost'],
            'manageHost' => $this->payload['manageHost'],
            'enable' => $result['enable']
        ]) . '\' > ' . LISTEN_FILE);

        FunMessageExit();
    }

    /**
     * 修改检出地址
     */
    function EditEnable()
    {
        $result = $this->SVNAdminInfo->GetSubversionListen(SVNSERVE_ENV_FILE, LISTEN_FILE);

        //enable的值可为 manageHost、bindHost

        //更新内容
        FunShellExec('echo \'' . json_encode([
            'bindPort' => $result['bindPort'],
            'bindHost' => $result['bindHost'],
            'manageHost' => $result['manageHost'],
            'enable' => $this->payload['enable']
        ]) . '\' > ' . LISTEN_FILE);

        FunMessageExit();
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
