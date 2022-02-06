<?php

declare(strict_types=1);

/*
 * 与计划任务操作相关
 */

class Crontab extends Controller
{
    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
        $this->Config = new Config();

        $this->svn_repository_path = SVN_REPOSITORY_PATH;
        $this->backup_path = BACKUP_PATH;
    }

    //立即执行计划任务
    function StartCrontab()
    {
    }
}
