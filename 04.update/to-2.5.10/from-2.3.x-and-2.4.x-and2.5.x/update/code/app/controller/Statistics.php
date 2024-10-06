<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
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

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceStatistics = new ServiceStatistics($parm);
    }

    /**
     * 获取状态
     * 
     * 负载状态
     * CPU使用率
     * 内存使用率
     */
    public function GetLoadInfo()
    {
        $result = $this->ServiceStatistics->GetLoadInfo();
        json2($result);
    }

    /**
     * 获取硬盘
     * 
     * 获取硬盘数量和每个硬盘的详细信息
     */
    public function GetDiskInfo()
    {
        $result = $this->ServiceStatistics->GetDiskInfo();
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
    public function GetStatisticsInfo()
    {
        $result = $this->ServiceStatistics->GetStatisticsInfo();
        json2($result);
    }
}
