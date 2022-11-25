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

        $result = funShellExec($cmdStart, true);

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
            return message();
        }

        $pid = trim(file_get_contents($this->configSvn['svnserve_pid_file']));
        if (empty($pid)) {
            return message();
        }

        if (!is_dir("/proc/$pid")) {
            return message();
        }

        $result = funShellExec(sprintf("kill -15 %s", $pid), true);

        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        if (is_dir("/proc/$pid")) {
            return message(200, 0, 'svnserve服务停止失败');
        }

        return message();
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
        $result = file_put_contents($this->configSvn['svn_conf_file'], $result);
        if (!$result) {
            return message(200, 0, sprintf('文件[%s]写入失败', $this->configSvn['svn_conf_file']));
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
        $result = file_put_contents($this->configSvn['svn_conf_file'], $result);
        if (!$result) {
            return message(200, 0, sprintf('文件[%s]写入失败', $this->configSvn['svn_conf_file']));
        }

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

    /**
     * 修改 svnserve.conf 文件的 password-db 值
     *
     * @param string $status passwd|httpPasswd
     * @return string|integer
     */
    public function UpdPasswddbInfo($status)
    {
        if (!in_array($status, [
            'passwd',
            'httpPasswd'
        ])) {
            return 0;
        }

        $con = $this->svnserveContent;

        preg_match_all("/^[ \t]*\[general\](((?!\n[ \t]*\[)[\s\S])*)/m", $con, $conPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $conPreg[0])) {
            $temp1 = trim($conPreg[1][0]);
            if (empty($temp1)) {
                return 1;
            } else {
                preg_match_all("/^[ \t]*(password-db)[ \t]*=[ \t]*(.*)[ \t]*$/m", $conPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg[0])) {
                    foreach ($resultPreg[1] as $key => $valueStr) {
                        $value = trim($resultPreg[2][$key]);
                        if ($value === $status) {
                            return $con;
                        } else {
                            return preg_replace("/^[ \t]*\[general\](((?!\n[ \t]*\[)[\s\S])*)/m", "[general]\n" . trim(preg_replace("/^[ \t]*(password-db)[ \t]*=[ \t]*(.*)[ \t]*$/m", "password-db = $status", $conPreg[1][0])) . "\n", $con);
                        }
                    }
                } else {
                    return 2;
                }
            }
        } else {
            return 3;
        }
    }

    /**
     * 获取 svnserve.conf 文件的 password-db 值
     *
     * @return boolean|integer|string
     */
    public function GetPasswddbInfo($status = '')
    {
        $con = $this->svnserveContent;

        preg_match_all("/^[ \t]*\[general\](((?!\n[ \t]*\[)[\s\S])*)/m", $con, $conPreg);
        if (preg_last_error() != 0) {
            return preg_last_error();
        }
        if (array_key_exists(0, $conPreg[0])) {
            $temp1 = trim($conPreg[1][0]);
            if (empty($temp1)) {
                return 1;
            } else {
                preg_match_all("/^[ \t]*(password-db)[ \t]*=[ \t]*(.*)[ \t]*$/m", $conPreg[1][0], $resultPreg);
                if (preg_last_error() != 0) {
                    return preg_last_error();
                }
                if (array_key_exists(0, $resultPreg[0])) {
                    foreach ($resultPreg[1] as $key => $valueStr) {
                        if (empty($status)) {
                            return trim($resultPreg[2][$key]);
                        } else {
                            return trim($resultPreg[2][$key]) === trim($status);
                        }
                    }
                } else {
                    return 2;
                }
            }
        } else {
            return 3;
        }
    }

    /**
     * 启用 svn 协议检出
     *
     * @return void
     */
    public function UpdSvnEnable()
    {
        //修改 svnserve.conf 为 passwd
        $result = $this->UpdPasswddbInfo('passwd');
        if (is_numeric($result)) {
            return message(200, 0, sprintf('更新[%s]配置信息失败-请及时检查[%s-%s]', $this->configSvn['svn_conf_file'], 1, $result));
        }
        file_put_contents($this->configSvn['svn_conf_file'], $result);

        //重启 svnserve
        $result = $this->UpdSvnserveStatusStop();
        // if ($result['status'] != 1) {
        //     return message($result['code'], $result['status'], $result['message'], $result['data']);
        // }
        sleep(1);
        $result = $this->UpdSvnserveStatusSart();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        return message();
    }
}
