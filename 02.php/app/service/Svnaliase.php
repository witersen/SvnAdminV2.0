<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

class Svnaliase extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 获取全部的SVN别名
     */
    public function GetAliaseList()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'searchKeyword' => ['type' => 'string', 'notNull' => false],
            'svnn_user_pri_path_id' => ['type' => 'integer', 'required' => $this->userRoleId == 2]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $searchKeyword = trim($this->payload['searchKeyword']);

        $result = $this->SVNAdmin->GetAliaseInfo($this->authzContent);

        if ($searchKeyword != '') {
            foreach ($result as $key => $value) {
                if (!strstr($value['aliaseName'], $searchKeyword) && !strstr($value['aliaseCon'], $searchKeyword)) {
                    unset($result[$key]);
                }
            }
        }

        //针对SVN用户可管理对象进行过滤
        if ($this->userRoleId == 2) {
            $filters = $this->database->select('svn_second_pri', [
                '[>]svn_user_pri_paths' => ['svnn_user_pri_path_id' => 'svnn_user_pri_path_id']
            ], [
                'svn_second_pri.svn_object_type(objectType)',
                'svn_second_pri.svn_object_name(objectName)',
            ], [
                'svn_user_pri_paths.svn_user_name' => $this->userName,
                'svn_user_pri_paths.svnn_user_pri_path_id' => $this->payload['svnn_user_pri_path_id']
            ]);
            foreach ($result as $key => $value) {
                if (!in_array([
                    'objectType' => 'aliase',
                    'objectName' => $value['aliaseName']
                ], $filters)) {
                    unset($result[$key]);
                }
            }
        }

        return message(200, 1, '成功', array_values($result));
    }
}
