<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-26 17:00:45
 * @Description: QQ:1801168257
 */

/**
 * 如果要修改
 * 需要在安装软件之前修改该值 安装过程中会自动读取并操作
 */
define('CONFIG_PATH', '/home/svnadmin/');

/**
 * SVN仓库父目录
 */
define('SVN_REPOSITORY_PATH', CONFIG_PATH . 'rep/');

/**
 * svnserve环境变量文件
 */
define('SVNSERVE_ENV_FILE', CONFIG_PATH . 'svnserve');

/**
 * SVN仓库权限配置文件
 */
define('SVN_CONF_FILE', CONFIG_PATH . 'svnserve.conf');

/**
 * authz文件
 */
define('SVN_AUTHZ_FILE', CONFIG_PATH . 'authz');

/**
 * passwd文件
 */
define('SVN_PASSWD_FILE', CONFIG_PATH . 'passwd');

/**
 * 备份文件夹
 */
define('SVN_BACHUP_PATH', CONFIG_PATH . 'backup/');

/**
 * 日志文件夹
 */
define('SVNADMIN_LOG_PATH', CONFIG_PATH . 'logs/');

/**
 * svnserve运行日志
 */
define('SVNSERVE_LOG_FILE', SVNADMIN_LOG_PATH . 'svnserve.log');

/**
 * 临时数据目录
 */
define('TEMP_PATH', CONFIG_PATH . 'temp/');

/**
 * mail配置 json文件
 */
define('MAIL_FILE', CONFIG_PATH . 'mail.json');

/**
 * svnserve监听端口和主机配置 json文件
 */
define('LISTEN_FILE', CONFIG_PATH . 'listen.json');

/**
 * 消息通知配置 json文件
 */
define('MESSAGE_FILE', CONFIG_PATH . 'message.json');
