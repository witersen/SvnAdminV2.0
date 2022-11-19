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
        $result = funShellExec(sprintf("'%s' -v", $this->configBin['saslauthd']), 'stderr');

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

        $result = funShellExec($cmdStart);

        if ($result['code'] != 0) {
            return message(200, 0, '启动进程失败: ' . $result['error']);
        }

        $result = funShellExec(sprintf("ps aux | grep '\-O %s' | awk 'NR==1' | awk '{print $2}'", $unique));
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

        $pid = file_get_contents($this->configSvn['saslauthd_pid_file']);
        if (empty($pid)) {
            return message();
        }

        $result = funShellExec(sprintf("kill -9 '%s'", $pid));

        if ($result['code'] == 0) {
            return message();
        } else {
            return message(200, 0, '停止服务失败: ' . $result['error']);
        }
    }

    /**
     * 开启 svn 使用 sasl 的选项
     *
     * @return void
     */
    public function UpdSvnSaslStart()
    {
        $con = file_get_contents($this->configSvn['svn_conf_file']);

        $result = $this->UpdUsesaslStatus($con, 'true');
        if (is_numeric($result)) {
            return message(200, 0, 'svn开启use-sasl失败');
        }
        if ($result == $con) {
            return message();
        }
        file_put_contents($this->configSvn['svn_conf_file'], $con);

        $result = $this->ServiceSvn->UpdSvnserveStatusStop();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        $result = $this->ServiceSvn->UpdSvnserveStatusSart();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        return message();
    }

    /**
     * 关闭 svn 使用 sasl 的选项
     *
     * @return void
     */
    public function UpdSvnSaslStop()
    {
        $con = file_get_contents($this->configSvn['svn_conf_file']);

        $result = $this->UpdUsesaslStatus($con, 'false');
        if (is_numeric($result)) {
            return message(200, 0, 'svn开启use-sasl失败');
        }
        if ($result == $con) {
            return message();
        }
        file_put_contents($this->configSvn['svn_conf_file'], $con);

        $this->ServiceSvn->UpdSvnserveStatusStop();
        $this->ServiceSvn->UpdSvnserveStatusSart();

        return message();
    }

    /**
     * 修改 svnserve.conf 文件的 use-sasl 值
     *
     * @param string $con
     * @param string $status
     * @return string|integer
     */
    private function UpdUsesaslStatus($con, $status)
    {
        $status = ($status === true || $status === 'true') ? 'true' : 'false';
        preg_match_all("/^[ \t]*\[sasl\](((?!\n[ \t]*\[)[\s\S])*)/m", $con, $conPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $conPreg[0])) {
            $temp1 = trim($conPreg[1][0]);
            if (empty($temp1)) {
                return preg_replace("/^[ \t]*\[sasl\](((?!\n[ \t]*\[)[\s\S])*)/m", "[sasl]\nuse-sasl = $status\n", $con);
            } else {
                preg_match_all("/^[ \t]*(use-sasl)[ \t]*=[ \t]*(.*)[ \t]*$/m", $conPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg[0])) {
                    foreach ($resultPreg[1] as $key => $valueStr) {
                        $value = trim($resultPreg[2][$key]);
                        if ($value === $status) {
                            return $con;
                        } else {
                            return preg_replace("/^[ \t]*\[sasl\](((?!\n[ \t]*\[)[\s\S])*)/m", "[sasl]\n" . trim(preg_replace("/^[ \t]*(use-sasl)[ \t]*=[ \t]*(.*)[ \t]*$/m", "use-sasl = $status", $conPreg[1][0])) . "\n", $con);
                        }
                    }
                } else {
                    return preg_replace("/^[ \t]*\[sasl\](((?!\n[ \t]*\[)[\s\S])*)/m", trim($conPreg[0][0]) . "\nuse-sasl = $status\n", $con);
                }
            }
        } else {
            return trim($con) . "\n[sasl]\nuse-sasl = $status\n";
        }
    }
}
