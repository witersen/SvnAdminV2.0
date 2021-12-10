<?php

/**
 * 守护进程状态探测
 * 0 超时
 * 1 打开
 * 2 关闭
 */
function DetectState()
{
    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

    socket_set_nonblock($sock);

    socket_connect($sock, IPC_ADDRESS, IPC_PORT);

    socket_set_block($sock);

    $v = array($sock);

    $state = @socket_select($r = $v, $w = $v, $f = $v, 5);

    socket_close($sock);

    return $state;
}
