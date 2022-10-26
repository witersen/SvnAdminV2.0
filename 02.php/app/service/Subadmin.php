<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-06 21:37:57
 * @Description: QQ:1801168257
 */

namespace app\service;

class Subadmin extends Base
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

        $this->Logs = new Logs();
    }

    /**
     * 获取子管理员列表
     *
     * @return void
     */
    public function GetSubadminList()
    {
        $pageSize = $this->payload['pageSize'];
        $currentPage = $this->payload['currentPage'];
        $searchKeyword = trim($this->payload['searchKeyword']);

        //分页
        $begin = $pageSize * ($currentPage - 1);

        $list = $this->database->select('subadmin', [
            'subadmin_id [Int]',
            'subadmin_name',
            // 'subadmin_phone',
            // 'subadmin_email',
            'subadmin_status [Int]',
            'subadmin_note',
            'create_time'
        ], [
            'AND' => [
                'OR' => [
                    'subadmin_name[~]' => $searchKeyword,
                    // 'subadmin_phone[~]' => $searchKeyword,
                    // 'subadmin_email[~]' => $searchKeyword,
                    'subadmin_note[~]' => $searchKeyword,
                ],
            ],
            'LIMIT' => [$begin, $pageSize],
            'ORDER' => [
                $this->payload['sortName']  => strtoupper($this->payload['sortType'])
            ]
        ]);

        $total = $this->database->count('subadmin', [
            'subadmin_id'
        ], [
            'AND' => [
                'OR' => [
                    'subadmin_name[~]' => $searchKeyword,
                    // 'subadmin_phone[~]' => $searchKeyword,
                    // 'subadmin_email[~]' => $searchKeyword,
                    'subadmin_note[~]' => $searchKeyword,
                ],
            ],
        ]);

        foreach ($list as $key => $value) {
            $list[$key]['subadmin_status'] = $value['subadmin_status'] == 1 ? true : false;
        }

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 创建子管理员
     *
     * @return void
     */
    public function CreateSubadmin()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'subadmin_name' => ['type' => 'string', 'notNull' => true],
            'subadmin_password' => ['type' => 'string', 'notNull' => true],
            'subadmin_note' => ['type' => 'string', 'notNull' => false],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        //检查重复
        $result = $this->database->get('subadmin', 'subadmin_id', [
            'subadmin_name' => $this->payload['subadmin_name']
        ]);
        if (!empty($result)) {
            return message(200, 0, '用户已存在');
        }

        //写入数据库
        $this->database->insert('subadmin', [
            'subadmin_name' => $this->payload['subadmin_name'],
            'subadmin_password' => md5($this->payload['subadmin_password']),
            'subadmin_status' => 1,
            'subadmin_note' => $this->payload['subadmin_note'],
            'create_time' => date('Y-m-d H:i:s')
        ]);

        //日志
        $this->Logs->InsertLog(
            '创建子管理员',
            sprintf("用户名:%s", $this->payload['subadmin_name']),
            $this->userName
        );

        return message();
    }

    /**
     * 删除子管理员
     *
     * @return void
     */
    public function DelSubadmin()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'subadmin_id' => ['type' => 'integer', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $subadminName = $this->database->get('subadmin', 'subadmin_name', [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);

        //从数据删除
        $this->database->delete('subadmin', [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);

        //日志
        $this->Logs->InsertLog(
            '删除子管理员',
            sprintf("用户名:%s", $subadminName),
            $this->userName
        );

        return message();
    }

    /**
     * 重置子管理员密码
     *
     * @return void
     */
    public function UpdSubadminPass()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'subadmin_id' => ['type' => 'integer', 'notNull' => true],
            'subadmin_password' => ['type' => 'string', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $subadminName = $this->database->get('subadmin', 'subadmin_name', [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);

        //修改密码
        $this->database->update('subadmin', [
            'subadmin_password' => md5($this->payload['subadmin_password'])
        ], [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);

        //日志
        $this->Logs->InsertLog(
            '修改子管理员密码',
            sprintf("用户名:%s", $subadminName),
            $this->userName
        );

        return message();
    }

    /**
     * 修改子管理员启用状态
     *
     * @return void
     */
    public function UpdSubadminStatus()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'subadmin_id' => ['type' => 'integer', 'notNull' => true],
            'status' => ['type' => 'boolean'],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $subadminName = $this->database->get('subadmin', 'subadmin_name', [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);

        //修改状态
        $this->database->update('subadmin', [
            'subadmin_status' => $this->payload['status'] == true ? 1 : 0
        ], [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);

        //日志
        $this->Logs->InsertLog(
            '修改子管理员状态',
            sprintf("用户名:%s 状态:%s", $subadminName, $this->payload['status'] == true ? '启用' : '禁用'),
            $this->userName
        );

        return message();
    }

    /**
     * 修改子管理员备注信息
     *
     * @return void
     */
    public function UpdSubadminNote()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'subadmin_id' => ['type' => 'integer', 'notNull' => true],
            'subadmin_note' => ['type' => 'string'],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $subadminName = $this->database->get('subadmin', 'subadmin_name', [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);

        //修改备注
        $this->database->update('subadmin', [
            'subadmin_note' => $this->payload['subadmin_note']
        ], [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);

        //日志
        $this->Logs->InsertLog(
            '修改子管理员备注',
            sprintf("用户名:%s", $subadminName),
            $this->userName
        );

        return message();
    }
}
