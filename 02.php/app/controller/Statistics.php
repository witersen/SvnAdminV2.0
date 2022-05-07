<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 14:00:06
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Statistics as ServiceStatistics;

class Statistics extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceStatistics;

    function __construct()
    {
        parent::__construct();

        $this->ServiceStatistics = new ServiceStatistics();
    }

    /**
     * 获取状态
     * 
     * 负载状态
     * CPU使用率
     * 内存使用率
     */
    public function GetSystemStatus()
    {
        $result = $this->ServiceStatistics->GetSystemStatus();
        json2($result);
    }

    /**
     * 获取硬盘
     * 
     * 获取硬盘数量和每个硬盘的详细信息
     */
    public function GetDisk()
    {
        $result = $this->ServiceStatistics->GetDisk();
        json2($result);
    }

    /**
     * 获取统计
     * 
     * 操作系统类型
     * 仓库占用体积
     * SVN仓库数量
     * SVN用户数量
     * SVN分组数量
     * 计划任务数量
     * 运行日志数量
     */
    public function GetSystemAnalysis()
    {
        $result = $this->ServiceStatistics->GetSystemAnalysis();
        json2($result);
    }
}
