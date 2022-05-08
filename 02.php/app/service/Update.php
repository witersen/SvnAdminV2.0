<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 20:01:24
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
        foreach ($this->config_update['update_server'] as $key => $value) {
            $versionInfo = FunCurlRequest($value);
            if ($versionInfo != null) {
                $versionInfo = json_decode($versionInfo, true);
                $latestVersion = $versionInfo['latestVersion'];
                if ($latestVersion == $this->config_version['version']) {
                    return message(200, 1, '当前版本为最新版');
                } else if ($latestVersion > $this->config_version['version']) {
                    return message(200, 1, '有更新', [
                        'latestVersion' => $versionInfo['latestVersion'],
                        'fixedContent' => implode('<br>', $versionInfo['fixedContent']) == '' ? '暂无内容' : implode('<br>', $versionInfo['fixedContent']),
                        'newContent' => implode('<br>', $versionInfo['newContent']) == '' ? '暂无内容' : implode('<br>', $versionInfo['newContent']),
                        'updateType' => $versionInfo['updateType'],
                        'updateStep' => $versionInfo['updateStep']
                    ]);
                } else if ($latestVersion < $this->config_version['version']) {
                    return message(200, 0, '系统版本错误');
                }
            }
        }
        return message(200, 0, '检测更新超时');
    }

    /**
     * 确认更新
     */
    public function StartUpdate()
    {
    }
}
