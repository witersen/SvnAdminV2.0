<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-06 21:38:05
 * @Description: QQ:1801168257
 */

namespace app\service;

class Safe extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 获取安全配置选项
     *
     * @return array
     */
    public function GetSafeConfig()
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
    public function SetSafeConfig()
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
        $result = $this->GetSafeConfig();

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
