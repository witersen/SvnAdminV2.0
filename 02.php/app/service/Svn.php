<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

// use Config;

class Svn extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
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

        if (!is_readable($this->configSvn['svnserve_env_file'])) {
            json1(200, 0, '文件' . $this->configSvn['svnserve_env_file'] . '不可读');
        }
        $svnserveContent = file_get_contents($this->configSvn['svnserve_env_file']);

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

        if (empty($svnserve_listen)) {
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
     * 启动 svnserve
     */
    public function UpdSvnserveStatusSart()
    {
        $result = $this->GetSvnserveListen();

        $cmdStart = sprintf(
            "'%s' --daemon --pid-file '%s' -r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s",
            $this->configBin['svnserve'],
            $this->configSvn['svnserve_pid_file'],
            $this->configSvn['rep_base_path'],
            $this->configSvn['svn_conf_file'],
            $this->configSvn['svnserve_log_file'],
            $result['bindPort'],
            $result['bindHost']
        );

        $result = funShellExec($cmdStart);

        if ($result['code'] == 0) {
            return message();
        } else {
            return message(200, 0, $result['error']);
        }
    }

    /**
     * 停止 svnserve
     */
    public function UpdSvnserveStatusStop()
    {
        if (!file_exists($this->configSvn['svnserve_pid_file'])) {
            return message(200, 0, 'pid文件不存在');
        }

        $pid = trim(file_get_contents($this->configSvn['svnserve_pid_file']));

        $result = funShellExec(sprintf("kill -9 '%s'", $pid));

        if ($result['code'] == 0) {
            return message();
        } else {
            return message(200, 0, $result['error']);
        }
    }
}
