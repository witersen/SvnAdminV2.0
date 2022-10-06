<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-12 16:59:04
 * @Description: QQ:1801168257
 */

/**
 * 如果要修改安装路径 ，可通过执行 server/install.php 来实现
 */

$home_path = '/home/svnadmin/';

$rep_base_path = $home_path . 'rep/';

$backup_base_path = $home_path . 'backup/';

$log_base_path = $home_path . 'logs/';

$templete_base_path = $home_path . 'templete/';

$templete_init_struct = $templete_base_path . 'initStruct/';

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
     * svnserve pid文件
     */
    'svnserve_pid_file' => $home_path . 'svnserve.pid',

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
    'recommend_hook_path' => $home_path . 'hooks/',

    /**
     * 备份目录
     */
    'backup_base_path' => $backup_base_path,

    /**
     * 任务计划目录
     */
    'crond_base_path' => $home_path . 'crond/',

    /**
     * 日志目录
     */
    'log_base_path' => $log_base_path,

    /**
     * svnserve 运行日志文件
     */
    'svnserve_log_file' => $log_base_path . 'svnserve.log',

    /**
     * 模板文件目录
     */
    'templete_base_path' => $templete_base_path,

    /**
     * 初始化仓库结构模板目录
     */
    'templete_init_struct' => $templete_init_struct,

    /**
     * 默认使用的仓库模板
     */
    'templete_init_struct_01' => $templete_init_struct . '01/',
];
