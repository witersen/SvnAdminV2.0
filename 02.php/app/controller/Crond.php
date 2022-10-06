<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 14:09:40
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

    function __construct()
    {
        parent::__construct();

        $this->ServiceCrond = new ServiceCrond();
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
    public function GetCrondList()
    {
        $result = $this->ServiceCrond->GetCrondList();
        json2($result);
    }

    /**
     * 设置任务计划
     *
     * @return array
     */
    public function SetCrond()
    {
        $result = $this->ServiceCrond->SetCrond();
        json2($result);
    }

    /**
     * 更新任务计划
     *
     * @return array
     */
    public function UpdCrond()
    {
        $result = $this->ServiceCrond->UpdCrond();
        json2($result);
    }

    /**
     * 修改任务计划状态
     *
     * @return array
     */
    public function UpdCrondStatus()
    {
        $result = $this->ServiceCrond->UpdCrondStatus();
        json2($result);
    }

    /**
     * 删除任务计划
     *
     * @return array
     */
    public function DelCrond()
    {
        $result = $this->ServiceCrond->DelCrond();
        json2($result);
    }

    /**
     * 获取日志信息
     *
     * @return array
     */
    public function GetLog()
    {
        $result = $this->ServiceCrond->GetLog();
        json2($result);
    }
}
