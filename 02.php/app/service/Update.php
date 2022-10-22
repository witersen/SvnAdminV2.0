<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-10 00:01:53
 * @Description: QQ:1801168257
 */

namespace app\service;

use Config;

class Update extends Base
{
    function __construct()
    {
        parent::__construct();
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
}
