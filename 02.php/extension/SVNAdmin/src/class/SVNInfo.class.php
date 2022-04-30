<?php
/*
 * @Author: witersen
 * @Date: 2022-04-27 17:58:13
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-30 19:28:20
 * @Description: QQ:1801168257
 * @copyright: https://github.com/witersen/
 */

namespace SVNAdmin\SVN;

class Info extends Core
{
    function __construct($authzFileContent, $passwdFileContent)
    {
        parent::__construct($authzFileContent, $passwdFileContent);
    }

    /**
     * 获取Subversion端口和主机情况
     * 
     * 先从Subversion配置文件获取绑定端口和主机
     * 然后与listen.json配置文件中的端口和主机进行对比和同步
     * 
     * 绑定端口
     * 绑定地址
     * 管理地址
     * 检出地址的启用地址
     */
    function GetSubversionListen($SVNSERVE_ENV_FILE, $LISTEN_FILE)
    {
        $bindPort = '';
        $bindHost = '';

        $svnserveContent = FunShellExec('cat ' . $SVNSERVE_ENV_FILE);
        $svnserveContent = $svnserveContent['result'];

        //匹配端口
        if (preg_match('/--listen-port[\s]+([0-9]+)/', $svnserveContent, $portMatchs) != 0) {
            $bindPort = trim($portMatchs[1]);
        }

        //匹配地址
        if (preg_match('/--listen-host[\s]+([\S]+)\b/', $svnserveContent, $hostMatchs) != 0) {
            $bindHost = trim($hostMatchs[1]);
        }

        $listenContent = FunShellExec('cat ' . $LISTEN_FILE);
        $listenContent = $listenContent['result'];

        if (!FunCheckJson($listenContent)) {
            //文件格式错误则初始化
            FunShellExec('echo \'' . json_encode([
                'bindPort' => $bindPort == '' ? '3690' : $bindPort,
                'bindHost' => $bindHost == '' ? '0.0.0.0' : $bindHost,
                'manageHost' => '127.0.0.1',
                'enable' => $bindHost == '' ? 'manageHost' : 'bindHost'
            ]) . '\' > ' . $LISTEN_FILE);
        } else {
            //更新内容
            $listenArray = json_decode($listenContent, true);
            if ($listenArray['bindPort'] != $bindPort) {
                $listenArray['bindPort'] = $bindPort == '' ? '3690' : $bindHost;
            }
            if ($listenArray['bindHost'] != $bindHost) {
                $listenArray['bindHost'] = $bindHost == '' ? '0.0.0.0' : $bindHost;
            }
            FunShellExec('echo \'' . json_encode([
                'bindPort' => $listenArray['bindPort'],
                'bindHost' => $listenArray['bindHost'],
                'manageHost' => $listenArray['manageHost'] == '' ? '127.0.0.1' : $listenArray['manageHost'],
                'enable' => $listenArray['enable']
            ]) . '\' > ' . $LISTEN_FILE);
        }

        $listenContent = FunShellExec('cat ' . $LISTEN_FILE);
        $listenContent = $listenContent['result'];
        $listenArray = json_decode($listenContent, true);

        return [
            'bindPort' => $listenArray['bindPort'],
            'bindHost' => $listenArray['bindHost'],
            'manageHost' => $listenArray['manageHost'],
            'enable' => $listenArray['enable'],
        ];
    }
}
