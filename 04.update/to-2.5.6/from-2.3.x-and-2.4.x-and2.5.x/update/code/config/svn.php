<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

 /**
  * 源码安装 - 修改数据存储主目录
  * 
  * 如果系统运行产生数据后 -> 修改安装路径 -> 需要执行 server/install.php
  * 如果系统未执行安装  -> 直接修改代码包的 config/svna.php 中的 $home 值后执行正常即可
  */

$home = '/home/svnadmin/';

return [
    /**
     * SVNAdmin软件配置信息的主目录
     */
    'home_path' => $home,

    /**
     * SVN仓库父目录
     */
    'rep_base_path' => $home . 'rep/',

    /**
     * svnserve环境变量文件
     */
    'svnserve_env_file' => $home . 'svnserve',

    /**
     * SVN仓库权限配置文件
     */
    'svn_conf_file' => $home . 'svnserve.conf',

    /**
     * authz文件
     */
    'svn_authz_file' => $home . 'authz',

    /**
     * passwd文件
     */
    'svn_passwd_file' => $home . 'passwd',

    /**
     * httpPasswd文件
     */
    'http_passwd_file' => $home . 'httpPasswd',

    /**
     * svnserve pid文件
     */
    'svnserve_pid_file' => $home . 'svnserve.pid',

    /**
     * httpd pid文件
     */
    'httpd_pid_file' => '/run/httpd/httpd.pid',

    /**
     * svnserve 自启动文件
     */
    'svnserve_service_file' => [
        'centos' => '/usr/lib/systemd/system/svnserve.service',
        'ubuntu' => '/lib/systemd/system/svnserve.service'
    ],

    /**
     * 推荐钩子目录
     */
    'recommend_hook_path' => $home . 'hooks/',

    /**
     * 备份目录
     */
    'backup_base_path' => $home . 'backup/',

    /**
     * 任务计划目录
     */
    'crond_base_path' => $home . 'crond/',

    /**
     * 日志目录
     */
    'log_base_path' => $home . 'logs/',

    /**
     * svnserve 运行日志文件
     */
    'svnserve_log_file' => $home . 'logs/svnserve.log',

    /**
     * 模板文件目录
     */
    'templete_base_path' => $home . 'templete/',

    /**
     * 初始化仓库结构模板目录
     */
    'templete_init_path' => $home . 'templete/initStruct/',

    /**
     * 默认使用的仓库模板
     */
    'templete_init_01_path' => $home . 'templete/initStruct/01/',

    /**
     * sasl目录
     */
    'sasl_home' => $home . 'sasl/',

    /**
     * ldap目录
     */
    'ldap_home' => $home . 'sasl/ldap',

    /**
     * ldap服务器配置文件
     */
    'ldap_config_file' => $home . 'sasl/ldap/saslauthd.conf',

    /**
     * saslauthd服务pid文件
     */
    'saslauthd_pid_file' => $home . 'sasl/saslauthd.pid',

    /**
     * apache modules 目录
     */
    'apache_modules_path' => '/etc/httpd/modules/',

    /**
     * apache svn 配置文件
     */
    'apache_subversion_file' => '/etc/httpd/conf.d/subversion.conf'
];
