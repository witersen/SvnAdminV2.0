<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 13:22:21
 * @Description: QQ:1801168257
 */

namespace app\service;

class Svnaliase extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $Logs;

    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 获取全部的SVN别名
     */
    public function GetAllAliaseList()
    {
        $searchKeyword = trim($this->payload['searchKeywordAliase']);

        $list = $this->SVNAdmin->GetAliaseInfo($this->authzContent);

        if ($searchKeyword != '') {
            foreach ($list as $key => $value) {
                if (!strstr($value['aliaseName'], $searchKeyword) && !strstr($value['aliaseCon'], $searchKeyword)) {
                    unset($list[$key]);
                }
            }
        }

        return message(200, 1, '成功', $list);
    }
}
