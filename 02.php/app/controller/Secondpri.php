<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\controller;

use app\service\Secondpri as ServiceSecondpri;

class Secondpri extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSecondpri;

    function __construct($parm)
    {
        parent::__construct($parm);

        $this->ServiceSecondpri = new ServiceSecondpri($parm);
    }

    /**
     * 设置二次授权状态
     *
     * @return void
     */
    public function UpdSecondpri()
    {
        $result = $this->ServiceSecondpri->UpdSecondpri();
        json2($result);
    }

    /**
     * 获取二次授权可管理对象
     *
     * @return void
     */
    public function GetSecondpriObjectList()
    {
        $result = $this->ServiceSecondpri->GetSecondpriObjectList();
        json2($result);
    }

    /**
     * 添加二次授权可管理对象
     *
     * @return void
     */
    public function CreateSecondpriObject()
    {
        $result = $this->ServiceSecondpri->CreateSecondpriObject();
        json2($result);
    }

    /**
     * 删除二次授权可管理对象
     *
     * @return void
     */
    public function DelSecondpriObject()
    {
        $result = $this->ServiceSecondpri->DelSecondpriObject();
        json2($result);
    }
}
