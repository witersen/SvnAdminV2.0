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
use app\service\Logs as ServiceLogs;
use Config;

class Setting extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSvn;
    private $ServiceLogs;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->ServiceSvn = new ServiceSvn();
    }

    /**
     * 修改 svnserve 的绑定端口
     */
    public function UpdSvnservePort()
    {
        //port不能为空 todo

        //获取现在的端口与要修改的端口对比检查是否相同
        $result = $this->ServiceSvn->GetSvnserveListen();

        if ($this->payload['bindPort'] == $result['bindPort']) {
            return message(200, 0, '无需更换，端口相同');
        }

        //停止svnserve
        $this->ServiceSvn->UpdSvnserveStatusStop();

        //重新构建配置文件内容
        $config = sprintf("OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"", $this->configSvn['rep_base_path'], $this->configSvn['svn_conf_file'], $this->configSvn['svnserve_log_file'], $this->payload['bindPort'], $result['bindHost']);

        //写入配置文件
        funFilePutContents($this->configSvn['svnserve_env_file'], $config);

        //启动svnserve
        $resultStart = $this->ServiceSvn->UpdSvnserveStatusSart();
        if ($resultStart['status'] != 1) {
            return $resultStart;
        }

        return message();
    }

    /**
     * 修改 svnserve 的绑定主机
     */
    public function UpdSvnserveHost()
    {
        //host不能为空
        //不能带前缀如http或者https

        //获取现在的绑定主机与要修改的主机对比检查是否相同
        $result = $this->ServiceSvn->GetSvnserveListen();

        if ($this->payload['bindHost'] == $result['bindHost']) {
            return message(200, 0, '无需更换，地址相同');
        }

        //停止svnserve
        $this->ServiceSvn->UpdSvnserveStatusStop();

        //重新构建配置文件内容
        $config = sprintf("OPTIONS=\"-r '%s' --config-file '%s' --log-file '%s' --listen-port %s --listen-host %s\"", $this->configSvn['rep_base_path'], $this->configSvn['svn_conf_file'], $this->configSvn['svnserve_log_file'], $result['bindPort'], $this->payload['bindHost']);

        //写入配置文件
        funFilePutContents($this->configSvn['svnserve_env_file'], $config);

        //启动svnserve
        $resultStart = $this->ServiceSvn->UpdSvnserveStatusSart();
        if ($resultStart['status'] != 1) {
            return $resultStart;
        }

        return message();
    }

    /**
     * 修改管理系统主机名
     */
    public function UpdManageHost()
    {
        //不能为空
        //不能带前缀如http或者https todo

        $result = $this->ServiceSvn->GetSvnserveListen();

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
    public function UpdCheckoutHost()
    {
        $result = $this->ServiceSvn->GetSvnserveListen();

        //更新
        $result['enable'] = $this->payload['enable'];
        if (!in_array($result['enable'], [
            'manageHost',
            'bindHost'
        ])) {
            return message(200, 0, '允许的值[manageHost|bindHost]');
        }
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
