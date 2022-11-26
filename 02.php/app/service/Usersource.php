<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use app\service\Sasl as ServiceSasl;
use app\service\Svn as ServiceSvn;

class Usersource extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSasl;
    private $ServiceSvn;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->ServiceSasl = new ServiceSasl();
        $this->ServiceSvn = new ServiceSvn();
    }

    /**
     * 保存用户来源配置信息
     *
     * @return void
     */
    public function UpdUsersourceInfo()
    {
        $checkResult = funCheckForm($this->payload, [
            'data_source' => ['type' => 'array', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $dataSource = $this->database->get('options', 'option_value', [
            'option_name' => 'data_source'
        ]);

        if (empty($dataSource)) {
            $this->database->insert('options', [
                'option_value' => serialize($this->payload['data_source']),
                'option_name' => 'data_source'
            ]);
        } else {
            $this->database->update('options', [
                'option_value' => serialize($this->payload['data_source']),
            ], [
                'option_name' => 'data_source'
            ]);
        }

        if ($this->payload['data_source']['user_source'] == 'ldap') {
            //清空数据库的用户
            $this->database->delete('svn_users', [
                'svn_user_id[>]' => 0
            ]);

            if ($this->payload['data_source']['group_source'] == 'ldap') {
                //清空数据库的分组
                $this->database->delete('svn_groups', [
                    'svn_group_id[>]' => 0
                ]);
            }

            //开启use-sasl
            $result = $this->ServiceSvn->UpdSvnSaslStart();
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message'], $result['data']);
            }

            //写入/etc/sasl2/svn.conf
            $unknown = '/etc/sasl2/svn.conf';
            funShellExec(sprintf("mkdir -p /etc/sasl2 && touch '%s' && chmod o+w '%s'", $unknown, $unknown));
            if (!is_readable($unknown)) {
                return message(200, 0, sprintf('文件[%s]不可读或不存在', $unknown));
            }
            file_put_contents('/etc/sasl2/svn.conf', "pwcheck_method: saslauthd\nmech_list: PLAIN LOGIN\n");

            //写入 sasl/ldap/saslauthd.conf
            $templeteSaslauthdPath = BASE_PATH . '/templete/sasl/ldap/saslauthd.conf';
            if (!is_readable($templeteSaslauthdPath)) {
                return message(200, 0, sprintf('文件[%s]不可读或不存在', $templeteSaslauthdPath));
            }
            $new = file_get_contents($templeteSaslauthdPath);

            $dataSource = $this->payload['data_source'];

            $ldap_servers = rtrim(trim($dataSource['ldap_host']), '/') . ':' . $dataSource['ldap_port'] . '/';
            $ldap_bind_dn = $dataSource['ldap_bind_dn'];
            $ldap_bind_pw = $dataSource['ldap_bind_password'];
            $ldap_search_base = $dataSource['user_base_dn'];
            $ldap_filter = $dataSource['user_attributes'];
            $ldap_version = $dataSource['ldap_version'];
            $ldap_password_attr = 'userPassword';

            $new = sprintf($new, $ldap_servers, $ldap_bind_dn, $ldap_bind_pw, $ldap_search_base, $ldap_filter, '%U', $ldap_version, $ldap_password_attr);

            if (!is_writable($this->configSvn['ldap_config_file'])) {
                return message(200, 0, sprintf('文件[%s]不可写或不存在', $this->configSvn['ldap_config_file']));
            }
            $old = file_get_contents($this->configSvn['ldap_config_file']);

            if ($new != $old) {
                file_put_contents($this->configSvn['ldap_config_file'], $new);

                sleep(1);

                //重启saslauthd
                $result = $this->ServiceSasl->UpdSaslStatusStop();
                // if ($result['status'] != 1) {
                //     return message($result['code'], $result['status'], $result['message'], $result['data']);
                // }
                sleep(1);
                $result = $this->ServiceSasl->UpdSaslStatusStart();
                if ($result['status'] != 1) {
                    return message($result['code'], $result['status'], $result['message'], $result['data']);
                }
            }
        } else {
            //关闭use-sasl
            $result = $this->ServiceSvn->UpdSvnSaslStop();
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message'], $result['data']);
            }
        }

        sleep(1);

        //重启 svnserve
        $result = $this->ServiceSvn->UpdSvnserveStatusStop();
        // if ($result['status'] != 1) {
        //     return message($result['code'], $result['status'], $result['message'], $result['data']);
        // }
        sleep(1);
        $result = $this->ServiceSvn->UpdSvnserveStatusSart();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        return message();
    }

    /**
     * 获取用户来源配置信息
     *
     * @return void
     */
    public function GetUsersourceInfo()
    {
        return message(200, 1, '成功', $this->dataSource);
    }
}
