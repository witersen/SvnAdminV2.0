<?php
/*
 * @Author: witersen
 * @Date: 2022-05-07 01:00:10
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-11 02:01:26
 * @Description: QQ:1801168257
 */

/**
 * 与守护进程通信
 */
function funShellExec($shell)
{
    $config_daemon = Config::get('daemon');

    $request = [
        'type' => 'passthru',
        'content' => $shell
    ];
    $request = json_encode($request);

    $length = $config_daemon['socket_data_length'];

    //检测信息长度
    if (strlen($request) >= $length) {
        json1(200, 0, '数据长度超过' . $length . ' 请向上调整参数：socket_data_length');
    }

    $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0) or exit('创建套接字失败：' . socket_strerror(socket_last_error()) . PHP_EOL);

    $server = socket_connect($socket, $config_daemon['ipc_file'], 0);

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
    funShellExec(sprintf("chmod 777 '%s'", $filename));

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
    $config_daemon = Config::get('daemon');

    $sock = @socket_create(AF_UNIX, SOCK_STREAM, 0);

    @socket_connect($sock, $config_daemon['ipc_file'], 0);

    socket_set_nonblock($sock);

    socket_set_block($sock);

    $state = @socket_select($r = [$sock], $w = [$sock], $f = [$sock], 5);

    socket_close($sock);

    return $state;
}
