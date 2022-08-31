<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-08 21:44:53
 * @Description: QQ:1801168257
 */

/**
 * 修改该配置文件后需要重启守护进程程序(svnadmind.php)
 */

return [

    /**
     * 程序与守护进程通信的本地地址
     * 不要修改
     */
    'IPC_ADDRESS' => '127.0.0.1',

    /**
     * 程序与守护进程通信的本地端口
     * 如与现有业务端口冲突 请自行修改
     */
    'IPC_PORT' => 6666,

    /**
     * socket_read 和 socket_write 的最大传输字节
     * 如果没有需要 8192 字节已经极大的满足需求
     */
    'SOCKET_READ_LENGTH' => 81920,
    'SOCKET_WRITE_LENGTH' => 81920,

    /**
     * socket 处理并发的最大队列长度
     */
    'SOCKET_LISTEN_BACKLOG' => 2000,
];
