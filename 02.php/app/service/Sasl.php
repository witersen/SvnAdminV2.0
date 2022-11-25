<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use app\service\Svn as ServiceSvn;

class Sasl extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSvn;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->ServiceSvn = new ServiceSvn();
    }

    /**
     * 获取当前的 sasl 信息
     *
     * @return void
     */
    public function GetSaslInfo()
    {
        $result = funShellExec(sprintf("'%s' -v", $this->configBin['saslauthd']));

        $result = $result['error'];

        $version = '-';
        if (preg_match('/^[\s]*saslauthd[\s]+(.*)/m', $result, $pregResult)) {
            $version = trim($pregResult[1]);
        }

        $mechanisms = '-';
        if (preg_match('/^[\s]*authentication mechanisms:[\s]+(.*)/m', $result, $pregResult)) {
            $mechanisms = trim($pregResult[1]);
        }

        $statusRun = true;
        if (!file_exists($this->configSvn['saslauthd_pid_file'])) {
            $statusRun = false;
        } else {
            $pid = trim(file_get_contents($this->configSvn['saslauthd_pid_file']));
            if (is_dir("/proc/$pid")) {
                $statusRun = true;
            } else {
                $statusRun = false;
            }
        }

        return message(200, 1, '成功', [
            'version' => $version,
            'mechanisms' => $mechanisms,
            'status' => $statusRun
        ]);
    }

    /**
     * 开启 saslauthd 服务
     *
     * @return void
     */
    public function UpdSaslStatusStart()
    {
        if (empty($this->configBin['saslauthd'])) {
            return message(200, 0, '未在 config/bin.php 文件中配置 saslauthd 路径');
        }

        if (file_exists($this->configSvn['saslauthd_pid_file'])) {
            $pid = trim(file_get_contents($this->configSvn['saslauthd_pid_file']));
            if (is_dir("/proc/$pid")) {
                return message(200, 0, '服务运行中');
            }
        }

        $unique = uniqid('saslauthd_');

        $cmdStart = sprintf(
            "'%s' -a '%s' -O '%s' -O '%s'",
            $this->configBin['saslauthd'],
            'ldap',
            $unique,
            $this->configSvn['ldap_config_file']
        );

        $result = funShellExec($cmdStart, true);

        if ($result['code'] != 0) {
            return message(200, 0, '启动进程失败: ' . $result['error']);
        }

        $result = funShellExec(sprintf("ps aux | grep -v grep | grep %s | awk 'NR==1' | awk '{print $2}'", $unique));
        if ($result['code'] != 0) {
            return message(200, 0, '获取进程失败: ' . $result['error']);
        }

        @file_put_contents($this->configSvn['saslauthd_pid_file'], trim($result['result']));
        if (!file_exists($this->configSvn['saslauthd_pid_file'])) {
            return message(200, 0, sprintf('无法写入文件[%s]-请为数据目录授权', $this->configSvn['saslauthd_pid_file']));
        }

        return message();
    }

    /**
     * 关闭 saslauthd 服务
     *
     * @return void
     */
    public function UpdSaslStatusStop()
    {
        if (!file_exists($this->configSvn['saslauthd_pid_file'])) {
            return message();
        }

        $pid = trim(file_get_contents($this->configSvn['saslauthd_pid_file']));
        if (empty($pid)) {
            return message();
        }

        if (!is_dir("/proc/$pid")) {
            return message();
        }

        $result = funShellExec(sprintf("kill -15 %s", $pid), true);

        if ($result['code'] != 0) {
            return message(200, 0, 'saslauthd服务停止失败: ' . $result['error']);
        }

        if (is_dir("/proc/$pid")) {
            return message(200, 0, 'saslauthd服务停止失败');
        }

        return message();
    }
}
