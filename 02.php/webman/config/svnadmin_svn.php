<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-03 22:19:27
 * @Description: QQ:1801168257
 */

/**
 * 如果要修改
 * 需要在安装软件之前修改该值 安装过程中会自动读取并操作
 */

$home_path = '/home/svnadmin/';

$rep_base_path = $home_path . 'rep/';

$backup_base_path = $home_path . 'backup/';

$log_base_path = $home_path . 'logs/';

$temp_base_path = $home_path . 'temp/';

$templete_base_path = $home_path . 'templete/';

return [
    /**
     * SVNAdmin软件配置信息的主目录
     */
    'home_path' => $home_path,

    /**
     * SVN仓库父目录
     */
    'rep_base_path' => $rep_base_path,

    /**
     * svnserve环境变量文件
     */
    'svnserve_env_file' => $home_path . 'svnserve',

    /**
     * SVN仓库权限配置文件
     */
    'svn_conf_file' => $home_path . 'svnserve.conf',

    /**
     * authz文件
     */
    'svn_authz_file' => $home_path . 'authz',

    /**
     * passwd文件
     */
    'svn_passwd_file' => $home_path . 'passwd',

    /**
     * 备份文件夹
     */
    'backup_base_path' => $backup_base_path,

    /**
     * 日志文件夹
     */
    'log_base_path' => $log_base_path,

    /**
     * svnserve运行日志
     */
    'svnserve_log_file' => $log_base_path . 'svnserve.log',

    /**
     * 临时数据目录
     */
    'temp_base_path' => $temp_base_path,

    /**
     * 模板文件目录
     */
    'templete_base_path' => $templete_base_path,

    /**
     * 初始化仓库结构模板
     */
    'templete_init_struct' => $templete_base_path . '01/',
];
