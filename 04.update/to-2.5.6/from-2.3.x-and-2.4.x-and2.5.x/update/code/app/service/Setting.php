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
use Config;

class Setting extends Base
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
    }

    /**
     * 获取宿主机配置
     *
     * @return array
     */
    public function GetDcokerHostInfo()
    {
        return message(200, 1, '成功', [
            'docker_host' => $this->dockerHost,
            'docker_svn_port' => $this->dockerSvnPort,
            'docker_http_port' => $this->dockerHttpPort,
        ]);
    }

    /**
     * 修改宿主机配置
     *
     * @return void
     */
    public function UpdDockerHostInfo()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'dockerHost' => ['type' => 'array', 'notNull' => true]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $checkResult = funCheckForm($this->payload['dockerHost'], [
            'docker_host' => ['type' => 'string', 'notNull' => true],
            'docker_svn_port' => ['type' => 'integer', 'notNull' => true],
            'docker_http_port' => ['type' => 'integer', 'notNull' => true]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        if (!preg_match('/^(?!(http|https):\/\/).*$/m', $this->payload['dockerHost']['docker_host'], $result)) {
            return message(200, 0, '主机地址无需携带协议前缀');
        }

        $this->database->update('options', [
            'option_value' => serialize($this->payload['dockerHost'])
        ], [
            'option_name' => '24_docker_host',
        ]);

        return message();
    }

    /**
     * 修改 svnserve 监听端口
     */
    public function UpdSvnservePort()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'listen_port' => ['type' => 'integer', 'notNull' => true]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        if ($this->payload['listen_port'] == $this->localSvnPort) {
            return message(200, 0, '无需更换，端口相同');
        }

        //停止
        (new ServiceSvn())->UpdSvnserveStatusStop();

        //重新构建配置文件内容
        $config = sprintf(
            "OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"",
            $this->configSvn['rep_base_path'],
            $this->configSvn['svn_conf_file'],
            $this->configSvn['svnserve_log_file'],
            $this->payload['listen_port'],
            $this->localSvnHost
        );

        //写入配置文件
        funFilePutContents($this->configSvn['svnserve_env_file'], $config);

        parent::RereadSvnserve();

        sleep(1);

        //启动
        $resultStart = (new ServiceSvn())->UpdSvnserveStatusStart();
        if ($resultStart['status'] != 1) {
            return $resultStart;
        }

        return message();
    }

    /**
     * 修改 svnserve 的监听主机
     */
    public function UpdSvnserveHost()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'listen_host' => ['type' => 'string', 'notNull' => true]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        if (!preg_match('/^(?!(http|https):\/\/).*$/m', $this->payload['listen_host'], $result)) {
            return message(200, 0, '主机地址无需携带协议前缀');
        }

        if ($this->payload['listen_host'] == $this->localSvnHost) {
            return message(200, 0, '无需更换，地址相同');
        }

        //停止
        (new ServiceSvn())->UpdSvnserveStatusStop();

        //重新构建配置文件内容
        $config = sprintf(
            "OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"",
            $this->configSvn['rep_base_path'],
            $this->configSvn['svn_conf_file'],
            $this->configSvn['svnserve_log_file'],
            $this->localSvnPort,
            $this->payload['listen_host']
        );

        //写入配置文件
        funFilePutContents($this->configSvn['svnserve_env_file'], $config);

        parent::RereadSvnserve();

        sleep(1);

        //启动
        $resultStart = (new ServiceSvn())->UpdSvnserveStatusStart();
        if ($resultStart['status'] != 1) {
            return $resultStart;
        }

        return message();
    }

    /**
     * 获取配置文件列表
     */
    public function GetDirInfo()
    {
        return message(200, 1, '成功', [
            [
                'key' => '主目录',
                'value' => $this->configSvn['home_path']
            ],
            [
                'key' => '仓库父目录',
                'value' => $this->configSvn['rep_base_path']
            ],
            [
                'key' => '仓库配置文件',
                'value' => $this->configSvn['svn_conf_file']
            ],
            [
                'key' => '仓库权限文件',
                'value' => $this->configSvn['svn_authz_file']
            ],
            [
                'key' => '用户账号文件',
                'value' => $this->configSvn['svn_passwd_file']
            ],
            [
                'key' => '备份目录',
                'value' => $this->configSvn['backup_base_path']
            ],
            [
                'key' => 'svnserve环境变量文件',
                'value' => $this->configSvn['svnserve_env_file']
            ],
        ]);
    }

    /**
     * 检测新版本
     */
    public function CheckUpdate()
    {
        $code = 200;
        $status = 0;
        $message = '更新服务器故障';

        $configVersion = Config::get('version');

        $configUpdate = Config::get('update');

        if (!function_exists('curl_init')) {
            return message(200, 0, '请先安装或启用php的curl扩展');
        }

        foreach ($configUpdate['update_server'] as $key1 => $value1) {

            $result = funCurlRequest(sprintf($value1['url'], $configVersion['version']));

            if (empty($result)) {
                continue;
            }

            //json => array
            $result = json_decode($result, true);

            if (!isset($result['code'])) {
                continue;
            }

            if ($result['code'] != 200) {
                $code = $result['code'];
                $status = $result['status'];
                $message = $result['message'];
                continue;
            }

            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        return message($code, $status, $message);
    }

    /**
     * 获取安全配置选项
     *
     * @return array
     */
    public function GetSafeInfo()
    {
        $safe_config = $this->database->get('options', [
            'option_value'
        ], [
            'option_name' => 'safe_config'
        ]);

        $safe_config_null = [
            [
                'name' => 'login_verify_code',
                'note' => '登录验证码',
                'enable' => true,
            ]
        ];

        if ($safe_config == null) {
            $this->database->insert('options', [
                'option_name' => 'safe_config',
                'option_value' => serialize($safe_config_null),
                'option_description' => ''
            ]);

            return message(200, 1, '成功', $safe_config_null);
        }

        if ($safe_config['option_value'] == '') {
            $this->database->update('options', [
                'option_value' => serialize($safe_config_null),
            ], [
                'option_name' => 'safe_config',
            ]);

            return message(200, 1, '成功', $safe_config_null);
        }

        return message(200, 1, '成功', unserialize($safe_config['option_value']));
    }

    /**
     * 设置安全配置选项
     *
     * @return array
     */
    public function UpdSafeInfo()
    {
        $this->database->update('options', [
            'option_value' => serialize($this->payload['listSafe'])
        ], [
            'option_name' => 'safe_config'
        ]);

        return message();
    }

    /**
     * 获取登录验证码选项
     *
     * @return array
     */
    public function GetVerifyOption()
    {
        $result = $this->GetSafeInfo();

        if ($result['status'] != 1) {
            return message(200, 0, '获取配置信息出错');
        }

        $safeConfig = $result['data'];
        $index = array_search('login_verify_code', array_column($safeConfig, 'name'));
        if ($index === false) {
            return message(200, 0, '获取配置信息出错');
        }

        return message(200, 1, '成功', $safeConfig[$index]);
    }
}
