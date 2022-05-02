<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-02 23:20:15
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
     * 获取svnserve端口和主机情况
     * 
     * 先从svnserve配置文件获取绑定端口和主机
     * 然后向数据库同步
     * 
     * 绑定端口
     * 绑定地址
     * 管理地址
     * 检出地址的启用地址
     */
    function GetSvnserveListen()
    {
        $bindPort = '';
        $bindHost = '';

        $svnserveContent = FunShellExec(sprintf("cat '%s'", SVNSERVE_ENV_FILE));
        $svnserveContent = $svnserveContent['result'];

        //匹配端口
        if (preg_match('/--listen-port[\s]+([0-9]+)/', $svnserveContent, $portMatchs) != 0) {
            $bindPort = trim($portMatchs[1]);
        }

        //匹配地址
        if (preg_match('/--listen-host[\s]+([\S]+)\b/', $svnserveContent, $hostMatchs) != 0) {
            $bindHost = trim($hostMatchs[1]);
        }

        $svnserve_listen = $this->database->get('options', [
            'option_value'
        ], [
            'option_name' => 'svnserve_listen'
        ]);

        $insert = [
            "bindPort" => $bindPort == '' ? 3690 : $bindPort,
            "bindHost" => $bindHost == '' ? '0.0.0.0' : $bindHost,
            "manageHost" => "127.0.0.1",
            "enable" => "manageHost"
        ];

        if ($svnserve_listen == null) {
            //插入
            $this->database->insert('options', [
                'option_name' => 'svnserve_listen',
                'option_value' => serialize($insert),
                'option_description' => ''
            ]);
        } else if ($svnserve_listen['option_value'] == '') {
            //更新
            $this->database->update('options', [
                'option_value' => serialize($insert),
            ], [
                'option_name' => 'svnserve_listen',
            ]);
        } else {
            //更新
            $svnserve_listen = unserialize($svnserve_listen['option_value']);
            $insert['manageHost'] = $svnserve_listen['manageHost'] == '' ? '127.0.0.1' : $svnserve_listen['manageHost'];
            $insert['enable'] = $svnserve_listen['enable'] == '' ? 'manageHost' : $svnserve_listen['enable'];
            $this->database->update('options', [
                'option_value' => serialize($insert),
            ], [
                'option_name' => 'svnserve_listen',
            ]);
        }

        return $insert;
    }

    /**
     * 获取Subversion的检出地址前缀
     * 
     * 先从Subversion配置文件获取绑定端口和主机
     * 然后与listen.json配置文件中的端口和主机进行对比和同步
     */
    function GetCheckout()
    {
        $result = $this->GetSvnserveListen();
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
        $bindInfo = $this->GetSvnserveListen();

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
        $result = $this->GetSvnserveListen();

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
        $result = $this->GetSvnserveListen();

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

        $result = $this->GetSvnserveListen();

        if ($this->payload['manageHost'] == $result['manageHost']) {
            FunMessageExit(200, 0, '无需更换，地址相同');
        }

        //更新
        $result['manageHost'] = $this->payload['manageHost'];
        $this->database->update('options', [
            'option_value' => serialize($result),
        ], [
            'option_name' => 'svnserve_listen',
        ]);

        FunMessageExit();
    }

    /**
     * 修改检出地址
     */
    function EditEnable()
    {
        $result = $this->GetSvnserveListen();

        //enable的值可为 manageHost、bindHost

        //更新
        $result['enable'] = $this->payload['enable'];
        $this->database->update('options', [
            'option_value' => serialize($result),
        ], [
            'option_name' => 'svnserve_listen',
        ]);

        FunMessageExit();
    }

    /**
     * 获取配置文件列表
     */
    function GetConfig()
    {
        FunMessageExit(200, 1, '成功', [
            [
                'key' => '仓库父目录',
                'value' => SVN_REPOSITORY_PATH
            ],
            [
                'key' => '仓库配置文件',
                'value' => SVN_CONF_FILE
            ],
            [
                'key' => '仓库权限文件',
                'value' => SVN_AUTHZ_FILE
            ],
            [
                'key' => '用户账号文件',
                'value' => SVN_PASSWD_FILE
            ],
            [
                'key' => '备份目录',
                'value' => SVN_BACHUP_PATH
            ],
            [
                'key' => 'svnserve环境变量文件',
                'value' => SVNSERVE_ENV_FILE
            ],
        ]);
    }
}
