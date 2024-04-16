<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

class Secondpri extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 设置二次授权状态
     *
     * @return void
     */
    public function UpdSecondpri()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'type' => ['type' => 'integer', 'notNull' => true],
            'svnn_user_pri_path_id' => ['type' => 'integer', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        if (!in_array($this->payload['type'], [0, 1])) {
            return message(200, 0, '不支持的类型');
        }

        if ($this->payload['type'] == 1) {
            $this->database->update('svn_user_pri_paths', [
                'second_pri' => 1
            ], [
                'svnn_user_pri_path_id' => $this->payload['svnn_user_pri_path_id']
            ]);
        } elseif ($this->payload['type'] == 0) {
            $this->database->update('svn_user_pri_paths', [
                'second_pri' => 0
            ], [
                'svnn_user_pri_path_id' => $this->payload['svnn_user_pri_path_id']
            ]);

            $this->database->delete('svn_second_pri', [
                'svnn_user_pri_path_id' => $this->payload['svnn_user_pri_path_id']
            ]);
        }

        return message();
    }

    /**
     * 获取二次授权可管理对象
     *
     * @return void
     */
    public function GetSecondpriObjectList()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'searchKeyword' => ['type' => 'string', 'notNull' => false],
            'svnn_user_pri_path_id' => ['type' => 'integer', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $searchKeyword = trim($this->payload['searchKeyword']);

        $result = $this->database->select('svn_second_pri', [
            'svn_second_pri_id [Int]',
            'svn_object_type(objectType)',
            'svn_object_name(objectName)'
        ], [
            'svnn_user_pri_path_id' => $this->payload['svnn_user_pri_path_id']
        ]);

        //过滤
        if (!empty($searchKeyword)) {
            foreach ($result as $key => $value) {
                if (
                    strstr($value['svn_object_name'], $searchKeyword) === false
                ) {
                    unset($result[$key]);
                }
            }
            $result = array_values($result);
        }

        return message(200, 1, '成功', $result);
    }

    /**
     * 添加二次授权可管理对象
     *
     * @return void
     */
    public function CreateSecondpriObject()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'svnn_user_pri_path_id' => ['type' => 'integer', 'notNull' => true],
            'objectType' => ['type' => 'string', 'notNull' => true],
            'objectName' => ['type' => 'string', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        if (!in_array($this->payload['objectType'], ['user', 'group', 'aliase'])) {
            return message(200, 0, '不允许的对象类型');
        }

        $userName = $this->database->get('svn_user_pri_paths', 'svn_user_name', [
            'svnn_user_pri_path_id' => $this->payload['svnn_user_pri_path_id']
        ]);
        if (empty($userName)) {
            return message(200, 0, '权限路径记录不存在');
        }

        if ($this->payload['objectType'] == 'user' && $this->payload['objectName'] == $userName) {
            return message(200, 0, '可管理对象不能包括本身');
        }

        $result = $this->database->get('svn_second_pri', 'svn_second_pri_id', [
            'svnn_user_pri_path_id' => $this->payload['svnn_user_pri_path_id'],
            'svn_object_type' => $this->payload['objectType'],
            'svn_object_name' => $this->payload['objectName']
        ]);

        if (!empty($result)) {
            return message(200, 0, '对象已存在');
        }

        $this->database->insert('svn_second_pri', [
            'svnn_user_pri_path_id' => $this->payload['svnn_user_pri_path_id'],
            'svn_object_type' => $this->payload['objectType'],
            'svn_object_name' => $this->payload['objectName']
        ]);

        return message();
    }

    /**
     * 删除二次授权可管理对象
     *
     * @return void
     */
    public function DelSecondpriObject()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'svn_second_pri_id' => ['type' => 'integer', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $this->database->delete('svn_second_pri', [
            'svn_second_pri_id' => $this->payload['svn_second_pri_id']
        ]);

        return message();
    }
}
