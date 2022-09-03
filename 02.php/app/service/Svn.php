<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-20 16:29:48
 * @Description: QQ:1801168257
 */

namespace app\service;

class Svn extends Base
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取Subversion运行状态 用于页头提醒
     */
    public function GetStatus()
    {
        $result = FunShellExec("ps auxf | grep -v 'grep' | grep svnserve");
        $result = $result['result'];

        if ($result == '') {
            return message(200, 0, 'svnserve服务未在运行，出于安全原因，SVN用户将无法使用系统的仓库在线内容浏览功能，其它功能不受影响');
        } else {
            return message();
        }
    }

    /**
     * 获取Subversion的检出地址前缀
     * 
     * 先从Subversion配置文件获取绑定端口和主机
     * 然后与listen.json配置文件中的端口和主机进行对比和同步
     */
    public function GetCheckout()
    {
        $result = $this->GetSvnserveListen();
        $checkoutHost = $result[$result['enable']];
        if ($result['bindPort'] != 3690) {
            $checkoutHost .= ':' . $result['bindPort'];
        }
        return message(200, 1, '成功', [
            'protocal' => 'svn://',
            'prefix' => $checkoutHost
        ]);
    }

    /**
     * 获取Subversion的安装情况
     * 
     * 0 未安装
     * 1 已安装未运行
     * 2 已安装运行中
     * -1 未知
     */
    public function GetSubversion()
    {
        //检测是否有正在运行的进程
        $isRun = FunShellExec('ps auxf|grep -v "grep"|grep svnserve');
        $isRun = $isRun['result'] == '' ? false : true;

        //检测安装程序是否存在于环境变量
        $isInstall =  FunShellExec('whereis svnserve');
        $isInstall = $isInstall['result'] == 'svnserve:' ? false : true;

        //运行中+未加入环境变量
        if ($isRun && !$isInstall) {
            return 2;
        }

        //运行中+已加入环境变量
        if ($isRun && $isInstall) {
            return 2;
        }

        //未运行+未加入环境变量
        if (!$isRun && !$isInstall) {
            return 0;
        }

        //未运行+已加入环境变量
        if (!$isRun && $isInstall) {
            return 1;
        }
    }

    /**
     * 获取Subversion的详细信息
     */
    public function GetDetail()
    {
        //获取绑定主机、端口等信息
        $bindInfo = $this->GetSvnserveListen();

        //获取安装和运行状态
        $installed = $this->GetSubversion();

        //获取Subversion版本
        $version = '-';
        if ($installed != 0) {
            $versionInfo = FunShellExec(sprintf("'%s' --version", $this->config_bin['svnserve']));
            $versionInfo = $versionInfo['result'];
            preg_match_all($this->config_reg['REG_SUBVERSION_VERSION'], $versionInfo, $versionInfoPreg);
            if (array_key_exists(0, $versionInfoPreg[0])) {
                $version = trim($versionInfoPreg[1][0]);
            } else {
                $version = '-';
            }
        }

        return message(200, 1, '成功', [
            'version' => $version,
            'installed' => $installed,
            'bindPort' => (int)$bindInfo['bindPort'],
            'bindHost' => $bindInfo['bindHost'],
            'manageHost' => $bindInfo['manageHost'],
            'enable' => $bindInfo['enable'],
            'svnserveLog' => $this->config_svn['svnserve_log_file']
        ]);
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
    public function GetSvnserveListen()
    {
        $bindPort = '';
        $bindHost = '';

        if (!is_readable($this->config_svn['svnserve_env_file'])) {
            json1(200, 0, '文件' . $this->config_svn['svnserve_env_file'] . '不可读');
        }
        $svnserveContent = file_get_contents($this->config_svn['svnserve_env_file']);

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
     * 安装SVN
     */
    public function Install()
    {
    }

    /**
     * 卸载SVN
     */
    public function UnInstall()
    {
    }

    /**
     * 启动SVN
     */
    public function Start()
    {
        FunShellExec("systemctl start svnserve");
        return message();
    }

    /**
     * 停止SVN
     */
    public function Stop()
    {
        FunShellExec("systemctl stop svnserve");
        return message();
    }

    /**
     * 修改svnserve的绑定端口
     */
    public function EditPort()
    {
        //port不能为空

        //获取现在的端口与要修改的端口对比检查是否相同
        $result = $this->GetSvnserveListen();

        if ($this->payload['bindPort'] == $result['bindPort']) {
            return message(200, 0, '无需更换，端口相同');
        }

        //停止svnserve
        FunShellExec('systemctl stop svnserve');

        //重新构建配置文件内容
        $config = sprintf("OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"", $this->config_svn['rep_base_path'], $this->config_svn['svn_conf_file'], $this->config_svn['svnserve_log_file'], $this->payload['bindPort'], $result['bindHost']);

        //写入配置文件
        FunFilePutContents($this->config_svn['svnserve_env_file'], $config);

        //启动svnserve
        $result = FunShellExec('systemctl start svnserve');

        if ($result['resultCode'] != 0) {
            return message(200, 0, '启动异常' . $result['error']);
        } else {
            return message();
        }
    }

    /**
     * 修改svnserve的绑定主机
     */
    public function EditHost()
    {
        //host不能为空
        //不能带前缀如http或者https

        //获取现在的绑定主机与要修改的主机对比检查是否相同
        $result = $this->GetSvnserveListen();

        if ($this->payload['bindHost'] == $result['bindHost']) {
            return message(200, 0, '无需更换，地址相同');
        }

        //停止svnserve
        FunShellExec('systemctl stop svnserve');

        //重新构建配置文件内容
        $config = sprintf("OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"", $this->config_svn['rep_base_path'], $this->config_svn['svn_conf_file'], $this->config_svn['svnserve_log_file'], $result['bindPort'], $this->payload['bindHost']);

        //写入配置文件
        FunFilePutContents($this->config_svn['svnserve_env_file'], $config);

        //启动svnserve
        $result = FunShellExec('systemctl start svnserve');

        if ($result['resultCode'] != 0) {
            return message(200, 0, '启动异常' . $result['error']);
        } else {
            return message();
        }
    }

    /**
     * 修改管理系统主机名
     */
    public function EditManageHost()
    {
        //不能为空
        //不能带前缀如http或者https

        $result = $this->GetSvnserveListen();

        if ($this->payload['manageHost'] == $result['manageHost']) {
            return message(200, 0, '无需更换，地址相同');
        }

        //更新
        $result['manageHost'] = $this->payload['manageHost'];
        $this->database->update('options', [
            'option_value' => serialize($result),
        ], [
            'option_name' => 'svnserve_listen',
        ]);

        return message();
    }

    /**
     * 修改检出地址
     */
    public function EditEnable()
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

        return message();
    }

    /**
     * 获取配置文件列表
     */
    public function GetConfig()
    {
        return message(200, 1, '成功', [
            [
                'key' => '主目录',
                'value' => $this->config_svn['home_path']
            ],
            [
                'key' => '仓库父目录',
                'value' => $this->config_svn['rep_base_path']
            ],
            [
                'key' => '仓库配置文件',
                'value' => $this->config_svn['svn_conf_file']
            ],
            [
                'key' => '仓库权限文件',
                'value' => $this->config_svn['svn_authz_file']
            ],
            [
                'key' => '用户账号文件',
                'value' => $this->config_svn['svn_passwd_file']
            ],
            [
                'key' => '备份目录',
                'value' => $this->config_svn['backup_base_path']
            ],
            [
                'key' => 'svnserve环境变量文件',
                'value' => $this->config_svn['svnserve_env_file']
            ],
        ]);
    }

    /**
     * 检测 authz 是否有效
     *
     * @return array
     */
    public function ValidateAuthz()
    {
        if (!array_key_exists('svnauthz-validate', $this->config_bin)) {
            return message(200, 0, '需要在 config/bin.php 文件中配置 svnauthz-validate 的路径');
        }

        if ($this->config_bin['svnauthz-validate'] == '') {
            return message(200, 0, '未在 config/bin.php 文件中配置 svnauthz-validate 路径');
        }

        $result = FunShellExec(sprintf("'%s' '%s'", '/usr/bin/svn-tools/svnauthz-validate', $this->config_svn['svn_authz_file'], $this->config_bin['svnauthz-validate']));
        if ($result['resultCode'] != 0) {
            return message(200, 2, '检测到异常', $result['error']);
        } else {
            return message(200, 1, 'authz文件配置无误');
        }
    }
}
