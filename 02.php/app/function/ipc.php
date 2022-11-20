<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

/**
 * 与守护进程通信
 */
function funShellExec($shell)
{
    $configDaemon = Config::get('daemon');

    $request = [
        'type' => 'passthru',
        'content' => $shell
    ];
    $request = json_encode($request);

    $length = $configDaemon['socket_data_length'];

    //检测信息长度
    if (strlen($request) >= $length) {
        json1(200, 0, '数据长度超过' . $length . ' 请向上调整参数：socket_data_length');
    }

    $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0) or exit('创建套接字失败：' . socket_strerror(socket_last_error()) . PHP_EOL);

    $server = socket_connect($socket, IPC_SVNADMIN, 0);

    socket_write($socket, $request, strlen($request));

    $reply = socket_read($socket, $length);

    socket_close($socket);

    return json_decode($reply, true);
}

/**
 * file_put_contents
 */
function funFilePutContents($filename, $data)
{
    file_put_contents($filename, $data);
}

/**
 * 守护进程状态探测
 * 0 超时
 * 1 打开
 * 2 关闭
 */
function funDetectState()
{
    $sock = @socket_create(AF_UNIX, SOCK_STREAM, 0);

    @socket_connect($sock, IPC_SVNADMIN, 0);

    socket_set_nonblock($sock);

    socket_set_block($sock);

    $state = @socket_select($r = [$sock], $w = [$sock], $f = [$sock], 5);

    socket_close($sock);

    return $state;
}
