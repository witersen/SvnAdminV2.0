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
     * 通信文件
     */
    'ipc_file' => '/tmp/svnadmind.socket',

    /**
     * socket_read 和 socket_write 的最大传输字节
     */
    'socket_data_length' => 81920,

    /**
     * socket 处理并发的最大队列长度
     */
    'socket_listen_backlog' => 2000,
];
