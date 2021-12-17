<?php

/**
 * 该配置文件修改前请关闭守护进程
 * 修改后重新启动后重新启动守护进程
 */

/**
 * 程序与守护进程通信的本地地址
 * 不需要修改
 */
define('IPC_ADDRESS', '127.0.0.1');

/**
 * 程序与守护进程通信的本地端口
 * 如与现有业务端口冲突 请自行修改
 */
define("IPC_PORT", '6666');

/**
 * socket_read 和 socket_write 的最大传输字节
 * 如果没有需要 8192 字节已经极大的满足需求
 */
define('SOCKET_READ_LENGTH', '8192');
define('SOCKET_WRITE_LENGTH', '8192');

/**
 * socket 处理并发的最大队列长度
 */
define('SOCKET_LISTEN_BACKLOG', '2000');

/**
 * 当前程序支持的最低PHP版本
 */
define('Required_PHP_VERSION', '7.3.0');

/**
 * 需要解除禁止的函数
 */
define('NEEDED_FUNCTION', array(
    'pcntl_fork',
    'pcntl_signal',
    'pcntl_wait',
    'shell_exec'
));

/**
 * socket_write 遇到的空字符串替代方案
 * 无需修改
 */
define('ISNULL', '-NULL-');