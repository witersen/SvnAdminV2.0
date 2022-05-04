<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 17:17:23
 * @Description: QQ:1801168257
 */

namespace app\controller;

use support\Request;

class Svn extends Core
{    
    /**
     * 获取Subversion运行状态 用于页头提醒
     */
    public function GetStatus(Request $request)
    {
        $result = shellPassthru("ps auxf | grep -v 'grep' | grep svnserve");
        $result = $result['result'];

        if ($result == '') {
            return message(200, 0, 'svnserve服务未在运行，SVN用户将无法使用仓库浏览功能');
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
    public function GetCheckout(Request $request)
    {
        $result = parent::GetSvnserveListen();
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
     * 获取Subversion的详细信息
     */
    public function GetDetail(Request $request)
    {
        //获取绑定主机、端口等信息
        $bindInfo = parent::GetSvnserveListen();

        //检测运行信息
        $runInfo = shellPassthru('ps auxf|grep -v "grep"|grep svnserve');
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
            $versionInfo = shellPassthru('svnserve --version');
            $versionInfo = $versionInfo['result'];
            preg_match_all($this->config_svnadmin_reg['REG_SUBVERSION_VERSION'], $versionInfo, $versionInfoPreg);
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
            'svnserveLog' => $this->config_svnadmin_svn['svnserve_log_file']
        ]);
    }

    /**
     * 安装SVN
     */
    public function Install(Request $request)
    {
    }

    /**
     * 卸载SVN
     */
    public function UnInstall(Request $request)
    {
    }

    /**
     * 启动SVN
     */
    public function Start(Request $request)
    {
        passthru("systemctl start svnserve");
        return message();
    }

    /**
     * 停止SVN
     */
    public function Stop(Request $request)
    {
        passthru("systemctl stop svnserve");
        return message();
    }

    /**
     * 修改svnserve的绑定端口
     */
    public function EditPort(Request $request)
    {
        //port不能为空

        //获取现在的端口与要修改的端口对比检查是否相同
        $result = parent::GetSvnserveListen();

        if ($this->payload['bindPort'] == $result['bindPort']) {
            return message(200, 0, '无需更换，端口相同');
        }

        //停止svnserve
        passthru('systemctl stop svnserve');

        //重新构建配置文件内容
        $config = sprintf("OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"", $this->config_svnadmin_svn['rep_base_path'], $this->config_svnadmin_svn['svn_conf_file'], $this->config_svnadmin_svn['svnserve_log_file'], $this->payload['bindPort'], $result['bindHost']);

        //写入配置文件
        passthru('echo \'' . $config . '\' > ' . $this->config_svnadmin_svn['svnserve_env_file']);

        //启动svnserve
        $result = shellPassthru('systemctl start svnserve');

        if ($result['resultCode'] != 0) {
            return message(200, 0, '启动异常' . $result['error']);
        } else {
            return message();
        }
    }

    /**
     * 修改svnserve的绑定主机
     */
    public function EditHost(Request $request)
    {
        //host不能为空
        //不能带前缀如http或者https

        //获取现在的绑定主机与要修改的主机对比检查是否相同
        $result = parent::GetSvnserveListen();

        if ($this->payload['bindHost'] == $result['bindHost']) {
            return message(200, 0, '无需更换，地址相同');
        }

        //停止svnserve
        passthru('systemctl stop svnserve');

        //重新构建配置文件内容
        $config = sprintf("OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"", $this->config_svnadmin_svn['rep_base_path'], $this->config_svnadmin_svn['svn_conf_file'], $this->config_svnadmin_svn['svnserve_log_file'], $result['bindPort'], $this->payload['bindHost']);

        //写入配置文件
        passthru('echo \'' . $config . '\' > ' . $this->config_svnadmin_svn['svnserve_env_file']);

        //启动svnserve
        $result = shellPassthru('systemctl start svnserve');

        if ($result['resultCode'] != 0) {
            return message(200, 0, '启动异常' . $result['error']);
        } else {
            return message();
        }
    }

    /**
     * 修改管理系统主机名
     */
    public function EditManageHost(Request $request)
    {
        //不能为空
        //不能带前缀如http或者https

        $result = parent::GetSvnserveListen();

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
    public function EditEnable(Request $request)
    {
        $result = parent::GetSvnserveListen();

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
    public function GetConfig(Request $request)
    {
        return message(200, 1, '成功', [
            [
                'key' => '仓库父目录',
                'value' => $this->config_svnadmin_svn['rep_base_path']
            ],
            [
                'key' => '仓库配置文件',
                'value' => $this->config_svnadmin_svn['svn_conf_file']
            ],
            [
                'key' => '仓库权限文件',
                'value' => $this->config_svnadmin_svn['svn_authz_file']
            ],
            [
                'key' => '用户账号文件',
                'value' => $this->config_svnadmin_svn['svn_passwd_file']
            ],
            [
                'key' => '备份目录',
                'value' => $this->config_svnadmin_svn['backup_base_path']
            ],
            [
                'key' => 'svnserve环境变量文件',
                'value' => $this->config_svnadmin_svn['svnserve_env_file']
            ],
        ]);
    }
}
