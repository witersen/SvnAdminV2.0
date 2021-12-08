<?php

//创建套接字
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create error");

//绑定地址和端口
socket_bind($socket, "127.0.0.1", "6666") or die("socket_bind error");

//设置可重复使用端口号
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//监听 设置并发队列的最大长度
socket_listen($socket, 5000);
while (true) {
    $clien = socket_accept($socket) or die("socket_accept error");

    //如果父进程不关心子进程什么时候结束,子进程结束后，内核会回
    //避免了正常情况下僵尸进程的产生
    pcntl_signal(SIGCHLD, SIG_IGN);

    $pid = pcntl_fork();
    if ($pid == -1) {
        die('fork error');
    } else if ($pid == 0) {
        handle_request($clien);
    } else {
    }
}

function handle_request($clien)
{
    //接收客户端发送的数据
    $data = socket_read($clien, 8192);

    //获取进程pid
    $id = posix_getpid();

    print_r($id . "\n");
    print_r($data . "\n");

    //执行
    $result = shell_exec($data);

    //处理没有返回内容的情况 否则 socket_write 遇到空内容会报错
    $result = $result == "" ? "-NULL-" : $result;

    //将结果返回给客户端
    socket_write($clien, $result, strlen($result)) or die("socket_write error");

    //关闭会话
    socket_close($clien);

    //退出进程
    exit();
}
