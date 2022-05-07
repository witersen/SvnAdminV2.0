<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 14:25:15
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
     * 获取当前版本信息
     */
    public function GetVersion()
    {
        return message(200, 1, '成功', [
            'current_verson' => $this->config_version['version'],
            'github' => 'https://github.com/witersen/svnAdminV2.0',
            'gitee' => 'https://gitee.com/witersen/SvnAdminV2.0',
            'author' => 'https://www.witersen.com'
        ]);
    }

    /**
     * 检测新版本
     */
    public function CheckUpdate($payload)
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
    public function StartUpdate($payload)
    {
    }
}
