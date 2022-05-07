<?php
/*
 * @Author: witersen
 * @Date: 2022-05-07 01:00:10
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 14:19:50
 * @Description: QQ:1801168257
 */

/**
 * 与守护进程通信
 */
function FunShellExec($shell)
{
    $config_daemon = Config::get('daemon');

    //检测信息长度
    if (strlen($shell) >= $config_daemon['SOCKET_READ_LENGTH']) {
        json1(200, 0, '数据长度超过' . $config_daemon['SOCKET_READ_LENGTH'] . ' 请向上调整参数：SOCKET_READ_LENGTH');
    }
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("error:" . socket_strerror(socket_last_error()));
    $server = socket_connect($socket, $config_daemon['IPC_ADDRESS'], (int)$config_daemon['IPC_PORT']);
    socket_write($socket, $shell);
    $reply = socket_read($socket, (int)$config_daemon['SOCKET_READ_LENGTH']);
    socket_close($socket);
    return unserialize($reply);
}

/**
 * 守护进程状态探测
 * 0 超时
 * 1 打开
 * 2 关闭
 */
function FunDetectState()
{
    $config_daemon = Config::get('daemon');

    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

    @socket_connect($sock, $config_daemon['IPC_ADDRESS'], (int)$config_daemon['IPC_PORT']);

    socket_set_nonblock($sock);

    socket_set_block($sock);

    $v = array($sock);

    $state = @socket_select($r = $v, $w = $v, $f = $v, 5);

    socket_close($sock);

    return $state;
}
