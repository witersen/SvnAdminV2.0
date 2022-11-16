<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Crond as ServiceCrond;

class Crond extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceCrond;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceCrond = new ServiceCrond($parm);
    }

    /**
     * 获取特殊结构的下拉列表
     *
     * @return void
     */
    public function GetRepList()
    {
        $result = $this->ServiceCrond->GetRepList();
        json2($result);
    }

    /**
     * 获取任务计划列表
     *
     * @return array
     */
    public function GetCrontabList()
    {
        $result = $this->ServiceCrond->GetCrontabList();
        json2($result);
    }

    /**
     * 设置任务计划
     *
     * @return array
     */
    public function CreateCrontab()
    {
        $result = $this->ServiceCrond->CreateCrontab();
        json2($result);
    }

    /**
     * 更新任务计划
     *
     * @return array
     */
    public function UpdCrontab()
    {
        $result = $this->ServiceCrond->UpdCrontab();
        json2($result);
    }

    /**
     * 修改任务计划状态
     *
     * @return array
     */
    public function UpdCrontabStatus()
    {
        $result = $this->ServiceCrond->UpdCrontabStatus();
        json2($result);
    }

    /**
     * 删除任务计划
     *
     * @return array
     */
    public function DelCrontab()
    {
        $result = $this->ServiceCrond->DelCrontab();
        json2($result);
    }

    /**
     * 获取日志信息
     *
     * @return array
     */
    public function GetCrontabLog()
    {
        $result = $this->ServiceCrond->GetCrontabLog();
        json2($result);
    }

    /**
     * 现在执行任务计划
     *
     * @return array
     */
    public function TriggerCrontab()
    {
        $result = $this->ServiceCrond->TriggerCrontab();
        json2($result);
    }

    /**
     * 检查 crontab at 是否安装和启动
     *
     * @return array
     */
    public function GetCronStatus()
    {
        $result = $this->ServiceCrond->GetCronStatus();
        json2($result);
    }
}
