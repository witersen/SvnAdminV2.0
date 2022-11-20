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
            //判断saslauthd服务有无开启
            // if (!file_exists($this->configSvn['saslauthd_pid_file'])) {
            //     return message(200, 0, sprintf('请先启动saslauthd服务-PID文件[%s]不存在或不可读', $this->configSvn['saslauthd_pid_file']));
            // }
            // $pid = file_get_contents($this->configSvn['saslauthd_pid_file']);
            // if (empty($pid)) {
            //     return message(200, 0, sprintf('请先启动saslauthd服务-PID文件[%s]内容为空', $this->configSvn['saslauthd_pid_file']));
            // }
            // if (!is_dir("/proc/$pid")) {
            //     return message(200, 0, sprintf('请先启动saslauthd服务-PID为[%s]的进程不存在', $pid));
            // }

            $this->database->delete('svn_users', [
                'svn_user_id[>]' => 0
            ]);

            if ($this->payload['data_source']['group_source'] == 'ldap') {
                //清空数据库的分组
                $this->database->delete('svn_groups', [
                    'svn_group_id[>]' => 0
                ]);
                //清空authz文件的分组
                $result = $this->SVNAdmin->ClearGroupSection($this->authzContent);
                if (is_numeric($result)) {
                    if ($result == 612) {
                        return message(200, 0, '文件格式错误(不存在[groups]标识)');
                    } else {
                        return message(200, 0, "错误码$result");
                    }
                }
                file_put_contents($this->configSvn['svn_authz_file'], $result);
                $this->authzContent = $result;
            }

            //开启use-sasl
            $result = $this->ServiceSvn->UpdSvnSaslStart();
            if ($result['status'] != 1) {
                return message($result['code'], $result['status'], $result['message'], $result['data']);
            }

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
                if ($result['status'] != 1) {
                    return message($result['code'], $result['status'], $result['message'], $result['data']);
                }
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

        //重启svnserve
        $result = $this->ServiceSvn->UpdSvnserveStatusStop();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }
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
        $dataSource = $this->database->get('options', 'option_value', [
            'option_name' => 'data_source'
        ]);

        $default = [
            //数据源
            'user_source' => 'ldap',
            'group_source' => 'ldap',

            //ldap服务器
            'ldap_host' => 'ldap://127.0.0.1/',
            'ldap_port' => 389,
            'ldap_version' => 3,
            'ldap_bind_dn' => '',
            'ldap_bind_password' => '',

            //用户相关
            'user_base_dn' => '',
            'user_search_filter' => '',
            'user_attributes' => '',

            //分组相关
            'group_base_dn' => '',
            'group_search_filter' => '',
            'group_attributes' => '',
            'groups_to_user_attribute' => '',
            'groups_to_user_attribute_value' => ''
        ];

        $dataSource = empty($dataSource) ? $default : unserialize($dataSource);

        return message(200, 1, '成功', $dataSource);
    }
}
