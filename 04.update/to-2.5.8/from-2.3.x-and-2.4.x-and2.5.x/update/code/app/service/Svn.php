<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use app\service\Apache as ServiceApache;

class Svn extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $ServiceApache;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->ServiceApache = new ServiceApache($parm);
    }

    /**
     * 获取 svnserve 的详细信息
     */
    public function GetSvnInfo()
    {
        return message(200, 1, '成功', [
            'enable' => $this->enableCheckout == 'svn',

            'version' => $this->GetSvnserveInfo(),
            'status' => $this->GetSvnserveStatus()['data'],
            'listen_port' => $this->localSvnPort,
            'listen_host' => $this->localSvnHost,
            'svnserve_log' => $this->configSvn['svnserve_log_file'],
            'password_db' => $this->configSvn['svn_passwd_file'],

            'sasl' => $this->GetSaslInfo(),

            'user_source' => $this->svnDataSource['user_source'],
            'group_source' => $this->svnDataSource['group_source'],

            'ldap' => $this->svnDataSource['ldap']
        ]);
    }

    /**
     * 获取 svnserve 信息
     *
     * @return void
     */
    private function GetSvnserveInfo()
    {
        $version = '-';
        $result = funShellExec(sprintf("'%s' --version", $this->configBin['svnserve']));
        if ($result['code'] == 0) {
            preg_match_all($this->configReg['REG_SUBVERSION_VERSION'], $result['result'], $versionInfoPreg);
            if (preg_last_error() != 0) {
                $version = 'PREG_ERROR';
            }
            if (array_key_exists(0, $versionInfoPreg[0])) {
                $version = trim($versionInfoPreg[1][0]);
            } else {
                $version = '--';
            }
        }

        return $version;
    }

    /**
     * 保存 svnserve 相关配置
     *
     * @return void
     */
    public function UpdSvnUsersource()
    {
        $checkResult = funCheckForm($this->payload, [
            'data_source' => ['type' => 'array', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $dataSource = $this->payload['data_source'];

        if ($dataSource['user_source'] == 'ldap') {

            if (substr($dataSource['ldap']['ldap_host'], 0, strlen('ldap://')) != 'ldap://' && substr($dataSource['ldap']['ldap_host'], 0, strlen('ldaps://')) != 'ldaps://') {
                return message(200, 0, 'ldap主机名必须以 ldap:// 或者 ldaps:// 开始');
            }

            if (preg_match('/\:[0-9]+/', $dataSource['ldap']['ldap_host'], $matches)) {
                return message(200, 0, 'ldap主机名不可携带端口');
            }

            if ($dataSource['group_source'] == 'ldap') {
                $checkResult = funCheckForm($dataSource['ldap'], [
                    'ldap_host' => ['type' => 'string', 'notNull' => true],
                    'ldap_port' => ['type' => 'integer'],
                    'ldap_version' => ['type' => 'integer'],
                    'ldap_bind_dn' => ['type' => 'string', 'notNull' => true],
                    'ldap_bind_password' => ['type' => 'string', 'notNull' => true],

                    'group_base_dn' => ['type' => 'string', 'notNull' => true],
                    'group_search_filter' => ['type' => 'string', 'notNull' => true],
                    'group_attributes' => ['type' => 'string', 'notNull' => true],
                    'groups_to_user_attribute' => ['type' => 'string', 'notNull' => true],
                    'groups_to_user_attribute_value' => ['type' => 'string', 'notNull' => true],
                ]);
                if ($checkResult['status'] == 0) {
                    return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
                }
            } else {
                $checkResult = funCheckForm($dataSource['ldap'], [
                    'ldap_host' => ['type' => 'string', 'notNull' => true],
                    'ldap_port' => ['type' => 'integer'],
                    'ldap_version' => ['type' => 'integer'],
                    'ldap_bind_dn' => ['type' => 'string', 'notNull' => true],
                    'ldap_bind_password' => ['type' => 'string', 'notNull' => true],

                    'user_base_dn' => ['type' => 'string', 'notNull' => true],
                    'user_search_filter' => ['type' => 'string', 'notNull' => true],
                    'user_attributes' => ['type' => 'string', 'notNull' => true],
                ]);
                if ($checkResult['status'] == 0) {
                    return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
                }
            }

            //清空数据库的用户
            $this->database->delete('svn_users', [
                'svn_user_id[>]' => 0
            ]);

            if ($dataSource['group_source'] == 'ldap') {
                //清空数据库的分组
                $this->database->delete('svn_groups', [
                    'svn_group_id[>]' => 0
                ]);
            }

            //开启use-sasl
            $result = $this->UpdSvnSaslStart();
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message'], $result['data']);
            }

            //写入/etc/sasl2/svn.conf
            $sasl2 = '/etc/sasl2/svn.conf';
            funShellExec(sprintf("mkdir -p /etc/sasl2 && touch '%s' && chmod o+w '%s'", $sasl2, $sasl2), true);
            if (!is_writable($sasl2)) {
                return message(200, 0, sprintf('文件[%s]不可读或不存在', $sasl2));
            }
            file_put_contents($sasl2, "pwcheck_method: saslauthd\nmech_list: PLAIN LOGIN\n");

            //写入 sasl/ldap/saslauthd.conf
            $templeteSaslauthdPath = BASE_PATH . '/templete/sasl/ldap/saslauthd.conf';
            if (!is_readable($templeteSaslauthdPath)) {
                return message(200, 0, sprintf('文件[%s]不可读或不存在', $templeteSaslauthdPath));
            }
            $new = file_get_contents($templeteSaslauthdPath);

            $ldap = $dataSource['ldap'];

            $ldap_servers = rtrim(trim($ldap['ldap_host']), '/') . ':' . $ldap['ldap_port'] . '/';
            $ldap_bind_dn = $ldap['ldap_bind_dn'];
            $ldap_bind_pw = $ldap['ldap_bind_password'];
            $ldap_search_base = $ldap['user_base_dn'];

            if (substr($ldap['user_search_filter'], 0, 1) == '(' && substr($ldap['user_search_filter'], -1) == ')') {
                $ldap_filter = $ldap['user_search_filter'];
            } else {
                $ldap_filter = '(' . $ldap['user_search_filter'] . ')';
            }

            $ldap_filter =  '(&(' . explode(',', $ldap['user_attributes'])[0] . '=%U)' . $ldap_filter . ')';
            $ldap_version = $ldap['ldap_version'];
            $ldap_password_attr = 'userPassword';

            $new = sprintf(
                $new,
                $ldap_servers,
                $ldap_bind_dn,
                $ldap_bind_pw,
                $ldap_search_base,
                $ldap_filter,
                $ldap_version,
                $ldap_password_attr
            );

            if (!is_writable($this->configSvn['ldap_config_file'])) {
                return message(200, 0, sprintf('文件[%s]不可写或不存在', $this->configSvn['ldap_config_file']));
            }
            $old = file_get_contents($this->configSvn['ldap_config_file']);

            if ($new != $old) {
                file_put_contents($this->configSvn['ldap_config_file'], $new);

                //重启saslauthd
                $result = $this->UpdSaslStatusStop();
                // if ($result['status'] != 1) {
                //     return message($result['code'], $result['status'], $result['message'], $result['data']);
                // }
                sleep(1);
                $result = $this->UpdSaslStatusStart();
                if ($result['status'] != 1) {
                    return message($result['code'], $result['status'], $result['message'], $result['data']);
                }
            }

            $this->database->update('options', [
                'option_value' => serialize([
                    'user_source' => $dataSource['user_source'],
                    'group_source' => $dataSource['group_source'],
                    'ldap' => $dataSource['ldap']
                ])
            ], [
                'option_name' => '24_svn_datasource'
            ]);
        } else {
            //关闭use-sasl
            $result = $this->UpdSvnSaslStop();
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message'], $result['data']);
            }

            $this->database->update('options', [
                'option_value' => serialize([
                    'user_source' => 'passwd',
                    'group_source' => 'authz',
                    'ldap' => $dataSource['ldap']
                ])
            ], [
                'option_name' => '24_svn_datasource'
            ]);
        }

        //重启 svnserve
        $result = $this->UpdSvnserveStatusStop();
        // if ($result['status'] != 1) {
        //     return message($result['code'], $result['status'], $result['message'], $result['data']);
        // }
        sleep(1);
        $result = $this->UpdSvnserveStatusStart();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        return message();
    }

    /**
     * 获取 sasl 信息
     *
     * @return array
     */
    private function GetSaslInfo()
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
            clearstatcache();
            if (is_dir("/proc/$pid")) {
                $statusRun = true;
            } else {
                $statusRun = false;
            }
        }

        return [
            'version' => $version,
            'mechanisms' => $mechanisms,
            'status' => $statusRun
        ];
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
            clearstatcache();
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

        sleep(1);

        $result = funShellExec(sprintf("ps aux | grep -v grep | grep %s | awk 'NR==1' | awk '{print $2}'", $unique));
        if ($result['code'] != 0) {
            return message(200, 0, '获取进程失败: ' . $result['error']);
        }

        funFilePutContents($this->configSvn['saslauthd_pid_file'], trim($result['result']), true);
        if (!file_exists($this->configSvn['saslauthd_pid_file'])) {
            return message(200, 0, sprintf('无法强制写入文件[%s]-请为数据目录授权', $this->configSvn['saslauthd_pid_file']));
        }
        if (file_get_contents($this->configSvn['saslauthd_pid_file']) !== trim($result['result'])) {
            return message(200, 0, '进程启动成功-但是写入pid文件失败-请联系管理员');
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

        clearstatcache();
        if (!is_dir("/proc/$pid")) {
            return message();
        }

        $result = funShellExec(sprintf("kill -15 %s", $pid), true);

        if ($result['code'] != 0) {
            return message(200, 0, 'saslauthd服务停止失败: ' . $result['error']);
        }

        sleep(1);

        clearstatcache();
        if (is_dir("/proc/$pid")) {
            return message(200, 0, 'saslauthd服务停止失败');
        }

        return message();
    }

    /**
     * 获取 svnserve 的运行状态
     */
    public function GetSvnserveStatus()
    {
        if ($this->enableCheckout == 'svn') {
            clearstatcache();

            $statusRun = true;

            if (!file_exists($this->configSvn['svnserve_pid_file'])) {
                $statusRun = false;
            } else {
                $pid = trim(file_get_contents($this->configSvn['svnserve_pid_file']));
                clearstatcache();
                if (is_dir("/proc/$pid")) {
                    $statusRun = true;
                } else {
                    $statusRun = false;
                }
            }

            return message(200, 1, $statusRun ? '服务正常' : 'svnserve服务未在运行，出于安全原因，SVN用户将无法使用系统的仓库在线内容浏览功能，其它功能不受影响', $statusRun);
        } else {
            return message();
        }
    }

    /**
     * 启动 svnserve
     */
    public function UpdSvnserveStatusStart()
    {
        $svnserveLog = false;
        if ($svnserveLog) {
            $cmdStart = sprintf(
                "'%s' --daemon --pid-file '%s' -r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s",
                $this->configBin['svnserve'],
                $this->configSvn['svnserve_pid_file'],
                $this->configSvn['rep_base_path'],
                $this->configSvn['svn_conf_file'],
                $this->configSvn['svnserve_log_file'],
                $this->localSvnPort,
                $this->localSvnHost
            );
        } else {
            $cmdStart = sprintf(
                "'%s' --daemon --pid-file '%s' -r '%s' --config-file '%s' --listen-port %s --listen-host %s",
                $this->configBin['svnserve'],
                $this->configSvn['svnserve_pid_file'],
                $this->configSvn['rep_base_path'],
                $this->configSvn['svn_conf_file'],
                $this->localSvnPort,
                $this->localSvnHost
            );
        }

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

        clearstatcache();
        if (!is_dir("/proc/$pid")) {
            return message();
        }

        $result = funShellExec(sprintf("kill -15 %s", $pid), true);

        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        sleep(1);

        clearstatcache();
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
    private function UpdSvnSaslStart()
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
    private function UpdSvnSaslStop()
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
     * 启用 svn 协议检出
     *
     * @return void
     */
    public function UpdSvnEnable()
    {
        //重启 svnserve
        $result = $this->UpdSvnserveStatusStop();
        // if ($result['status'] != 1) {
        //     return message($result['code'], $result['status'], $result['message'], $result['data']);
        // }
        sleep(1);
        $result = $this->UpdSvnserveStatusStart();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        //清空数据库的用户
        $this->database->delete('svn_users', [
            'svn_user_id[>]' => 0
        ]);

        //切换目前状态
        $this->database->update('options', [
            'option_value' => 'svn',
        ], [
            'option_name' => '24_enable_checkout',
        ]);

        //禁用 http 协议检出
        $result = $this->ServiceApache->UpdSubversionDisable();

        //重启 httpd
        funShellExec(sprintf("'%s' -k graceful", $this->configBin['httpd']), true);

        return message();
    }

    /**
     * 禁用 svn 协议检出
     *
     * @return void
     */
    public function UpdSvnDisable()
    {
        $this->UpdSvnserveStatusStop();

        return message();
    }
}
