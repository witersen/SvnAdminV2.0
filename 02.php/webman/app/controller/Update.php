<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 17:32:56
 * @Description: QQ:1801168257
 */

namespace app\controller;

use support\Request;

class Update extends Core
{    
    /**
     * 获取当前版本信息
     */
    public function GetVersion()
    {
        return message(200, 1, '成功', [
            'current_verson' => $this->config_svnadmin_version['version'],
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
        foreach ($this->config_svnadmin_update['update_server'] as $key => $value) {
            $versionInfo = FunCurlRequest($value);
            if ($versionInfo != null) {
                $versionInfo = json_decode($versionInfo, true);
                $latestVersion = $versionInfo['latestVersion'];
                if ($latestVersion == $this->config_svnadmin_version['version']) {
                    $data['status'] = 1;
                    $data['message'] = '当前版本为最新版';
                    $data['data'] = null;
                    return $data;
                } else if ($latestVersion > $this->config_svnadmin_version['version']) {
                    $data['status'] = 1;
                    $data['message'] = '有更新';
                    $data['data'] = array(
                        'latestVersion' => $versionInfo['latestVersion'],
                        'fixedContent' => implode('<br>', $versionInfo['fixedContent']) == '' ? '暂无内容' : implode('<br>', $versionInfo['fixedContent']),
                        'newContent' => implode('<br>', $versionInfo['newContent']) == '' ? '暂无内容' : implode('<br>', $versionInfo['newContent']),
                        'updateType' => $versionInfo['updateType'],
                        'updateStep' => $versionInfo['updateStep']
                    );
                    return $data;
                } else if ($latestVersion < $this->config_svnadmin_version['version']) {
                    $data['status'] = 0;
                    $data['message'] = '系统版本错误';
                    $data['data'] = null;
                    return $data;
                }
            }
        }
        $data['status'] = 0;
        $data['message'] = '检测更新超时';
        return $data;
    }

    /**
     * 确认更新
     */
    public function StartUpdate($payload)
    {
    }
}
