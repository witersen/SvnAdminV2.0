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
function funShellExec($shell, $daemon = false)
{
    if ($daemon) {
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

        $reply = @socket_read($socket, $length);

        socket_close($socket);

        return json_decode($reply, true);
    } else {
        //定义错误输出文件路径
        $stderrFile = tempnam(sys_get_temp_dir(), 'svnadmin_');

        //将标准错误重定向到文件
        //使用状态码来标识错误信息
        ob_start();
        //解决中文乱码问题
        passthru('export LC_CTYPE=en_US.UTF-8 && ' . $shell . " 2>$stderrFile", $code);
        $buffer = ob_get_contents();
        ob_end_clean();

        //将错误信息和正确信息分类收集
        $result = [
            'code' => $code,
            'result' => trim($buffer),
            'error' => file_get_contents($stderrFile)
        ];

        @unlink($stderrFile);

        return $result;
    }
}

/**
 * file_put_contents
 */
function funFilePutContents($filename, $data, $daemon = false, $chmod = '777')
{
    if ($daemon) {
        funShellExec(sprintf("touch '%s'", $filename), true);
        funShellExec(sprintf("chmod %s '%s'", $chmod, $filename), true);
    }
    @file_put_contents($filename, $data);
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

    $read_socks = [$sock];
    $write_socks = [$sock];
    $except_socks = [$sock];

    $state = @socket_select($read_socks, $write_socks, $except_socks, 5);

    socket_close($sock);

    return $state;
}
