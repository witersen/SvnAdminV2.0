<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-10 00:01:53
 * @Description: QQ:1801168257
 */

namespace app\service;

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
        foreach ($this->config_update['update_server'] as $key1 => $value1) {

            $json = FunCurlRequest($value1['url']);

            if ($json == null) {
                continue;
            }

            //json => array
            $array = json_decode($json, true);

            $last = $array['version'];

            if ($this->config_version['version'] == $last) {
                return message(200, 1, '当前为最新版');
            }

            if ($this->config_version['version'] < $last) {
                return message(200, 1, '有新版本', $array);
            }
        }

        return message(200, 0, '检测超时');
    }
}
