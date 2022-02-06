<?php

declare(strict_types=1);

/**
 * 修改频率较高的配置项
 */

/**
 * 管理系统管理员账号和密码
 */
define("MANAGE_USER", "administrator");
define("MANAGE_PASS", "administrator");
define("MANAGE_EMAIL", "1801168257@qq.com");

/*
 * jwt的token模式中的签名
 * 用户第一次使用后可选择重新设置
 */
define("SIGNATURE", "QOWIREUQWIOFN");

/**
 * 临时数据目录
 */
define("TEMP_PATH", "/home/svnadmin/temp");

/**
 * SVN存储库目录
 */
define("SVN_REPOSITORY_PATH", "/home/svnadmin/rep");

/**
 * 备份文件存储路径
 */
define("BACKUP_PATH", "/home/svnadmin/backup");

/**
 * 日志路径
 */
define("LOG_PATH", "/home/svnadmin/log");

/**
 * SVN所有仓库的统一配置文件路径
 */
define("SVN_SERVER_CONF", "/home/svnadmin/svnserve.conf");
define("SVN_SERVER_PASSWD", "/home/svnadmin/passwd");
define("SVN_SERVER_AUTHZ", "/home/svnadmin/authz");

/**
 * 当前使用的svn协议类型
 */
define("SVN_PROTOCOL", "svn");

/**
 * SVN服务的端口
 */
define("SVN_PORT", "3690");

/**
 * 服务器IP地址
 */
define("SERVER_IP", "127.0.0.1");

/**
 * 服务器域名
 */
define("SERVER_DOMAIN", "localhost");

/**
 * http服务的端口
 */
define("HTTP_PORT", "80");

/**
 * 邮件配置相关
 */
//系统是否启用邮件题型
define("ALL_MAIL_STATUS", "0");
//邮件服务器协议类型
define("mail_protocol_type", "SMTP");
//邮件服务器地址
define("mail_host", "smtp.qq.com");
//邮件服务器端口
define("mail_port", "587");
//邮件服务器ssl端口
define("mail_ssl_port", "0");
//邮件服务器用户账号
define("mail_user", "1801168257");
//邮件服务器用户密码
define("mail_password", "fybbookmsiuvdaeg");
//发件人
define("send_mail", "1801168257@qq.com");
//是否启用该邮件服务器配置
define("single_mail_status", "1");

/**
 * curl请求超时时间
 */
define("CURL_TIMEOUT", 5);

/**
 * 当前软件版本信息
 * 用户请不要自行修改 以免影响后续升级检测
 */
define("VERSION", "2.2.2");

/**
 * 升级服务器地址
 */
define("UPDATE_SERVER", array(
    "https://gitee.com/witersen/update/raw/master/SvnAdmin/update.json",
    "https://raw.githubusercontent.com/witersen/update/master/SvnAdmin/update.json"
));
