<?php

/*
 * sqlite 数据库信息
 * DB_FILE 为默认路径 用户可自由修改
 * 用户自行修改路径后要保证数据库文件及文件所在目录对web用户有执行权限
 */
define("DB_TYPE", "sqlite");
define("DB_FILE", "/usr/local/svnadmin/svnadmin.db");

/*
 * jwt的token模式中的签名
 * 用户使用后请修改打乱
 */
define("SIGNATURE", "7HF89EKGK9EG");

/**
 * 程序与守护进程通信的本地端口
 * 如与现有业务端口冲突 请自行修改
 */
define("IPC_PORT", 6666);

/**
 * 程序与守护进程通信的本地地址
 * 不需要修改
 */
define("IPC_ADDRESS", '127.0.0.1');

/**
 * socket_write 遇到的空字符串替代方案
 * 一般情况下无需修改
 */
define("ISNULL", "-NULL-");

/**
 * socket_read 和 socket_write 的最大传输字节
 * 如果没有需要 8192 字节已经极大的满足需求
 */
define("SOCKET_READ_LENGTH", 8192);
define("SOCKET_WRITE_LENGTH", 8192);

/**
 * socket 处理并发的最大队列长度
 */
define("SOCKET_LISTEN_BACKLOG", 2000);

/**
 * 当前软件版本信息
 * 用户请不要自行修改 以免影响后续升级检测
 */
define('VERSION', '2.1.1');

/**
 * 升级服务器地址
 * 用户请不要自行修改 以免影响后续升级检测
 */
define("UPDATE_SERVER", "");
