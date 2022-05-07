<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-07 14:08:55
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Update as ServiceUpdate;

class Update extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceUpdate;

    function __construct()
    {
        parent::__construct();

        $this->ServiceUpdate = new ServiceUpdate();
    }

    /**
     * 获取当前版本信息
     */
    public function GetVersion()
    {
        $result = $this->ServiceUpdate->GetVersion();
        json2($result);
    }

    /**
     * 检测新版本
     */
    public function CheckUpdate()
    {
        $result = $this->ServiceUpdate->CheckUpdate();
        json2($result);
    }

    /**
     * 确认更新
     */
    public function StartUpdate()
    {
        $result = $this->ServiceUpdate->StartUpdate();
        json2($result);
    }
}
