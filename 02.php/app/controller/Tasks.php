<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Tasks as ServiceTasks;

class Tasks extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceTasks;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceTasks = new ServiceTasks($parm);
    }

    /**
     * 获取后台任务实时日志
     */
    public function GetTaskRun()
    {
        $result = $this->ServiceTasks->GetTaskRun();
        json2($result);
    }

    /**
     * 获取后台任务队列
     */
    public function GetTaskQueue()
    {
        $result = $this->ServiceTasks->GetTaskQueue();
        json2($result);
    }

    /**
     * 获取后台任务执行历史
     */
    public function GetTaskHistory()
    {
        $result = $this->ServiceTasks->GetTaskHistory();
        json2($result);
    }

    /**
     * 获取历史任务日志
     *
     * @return void
     */
    public function GetTaskHistoryLog()
    {
        $result = $this->ServiceTasks->GetTaskHistoryLog();
        json2($result);
    }

    /**
     * 删除历史执行任务
     *
     * @return void
     */
    public function DelTaskHistory()
    {
        $result = $this->ServiceTasks->DelTaskHistory();
        json2($result);
    }

    /**
     * 停止后台任务
     */
    public function UpdTaskStop()
    {
        $result = $this->ServiceTasks->UpdTaskStop();
        json2($result);
    }
}
