<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
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
            'subadmin_status [Int]',
            'subadmin_note',
            'subadmin_create_time',
            'subadmin_last_login',
            'subadmin_token'
        ], [
            'ORDER' => [
                $this->payload['sortName']  => strtoupper($this->payload['sortType'])
            ]
        ]);

        //过滤
        if (!empty($searchKeyword)) {
            foreach ($list as $key => $value) {
                if (
                    strstr($value['subadmin_name'], $searchKeyword) === false &&
                    strstr($value['subadmin_note'], $searchKeyword) === false
                ) {
                    unset($list[$key]);
                }
            }
            $list = array_values($list);
        }

        //总计
        $total = empty($list) ? 0 : count($list);

        //分页
        $list = array_slice($list, $begin, $pageSize);

        $time = time();
        foreach ($list as $key => $value) {
            $list[$key]['subadmin_status'] = $value['subadmin_status'] == 1 ? true : false;
            $list[$key]['online'] = (empty($value['subadmin_token']) || $value['subadmin_token'] == '-') ? false : (explode($this->configSign['signSeparator'], $value['subadmin_token'])[3] > $time);
            unset($list[$key]['subadmin_token']);
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
            'subadmin_last_login' => '',
            'subadmin_create_time' => date('Y-m-d H:i:s'),
            'subadmin_tree' => json_encode($this->subadminTree),
            'subadmin_functions' => json_encode($this->GetPriFunctions($this->subadminTree))
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

    /**
     * 获取某个子管理员的权限树
     */
    public function GetSubadminTree()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'subadmin_id' => ['type' => 'integer', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $tree = $this->database->get('subadmin', 'subadmin_tree', [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);
        $tree = json_decode($tree, true);

        if (empty($tree)) {
            $this->database->update('subadmin', [
                'subadmin_tree' => json_encode($this->subadminTree)
            ], [
                'subadmin_id' => $this->payload['subadmin_id']
            ]);

            return message(200, 1, '成功', [
                'tree' => $this->subadminTree,
                'treeOld' => [],
                'needUpdateTree' => false
            ]);
        }

        $needUpdateTree = $this->SubadminTreeFormat($this->subadminTree) != $this->SubadminTreeFormat($tree);

        $treeOld = [];
        if ($needUpdateTree) {
            $treeOld = $this->SubadminTreeDisable($tree);
            $tree = $this->subadminTree;
        }

        return message(200, 1, '成功', [
            'tree' => $tree,
            'treeOld' => $treeOld,
            'needUpdateTree' => $needUpdateTree
        ]);
    }

    /**
     * 统一权限树
     *
     * @param array $tree
     * @return array
     */
    private function SubadminTreeFormat($tree)
    {
        if (empty($tree)) {
            return [];
        }

        $result = [];
        foreach ($tree as $node) {
            $temp = [];
            $temp['title'] = $node['title'];
            $temp['expand'] = true;
            $temp['checked'] = true;
            $temp['disabled'] = true;
            if (isset($node['router_name'])) {
                $temp['router_name'] = $node['router_name'];
            }
            $temp['necessary_functions'] = $node['necessary_functions'];
            $temp['children'] = $this->SubadminTreeFormat($node['children']);
            $result[] = $temp;
        }

        return $result;
    }

    /**
     * 统一禁用权限树
     *
     * @param array $tree
     * @return array
     */
    private function SubadminTreeDisable($tree)
    {
        if (empty($tree)) {
            return [];
        }

        $result = [];
        foreach ($tree as $node) {
            $temp = [];
            $temp['title'] = $node['title'];
            $temp['expand'] = $node['expand'];
            $temp['checked'] = $node['checked'];
            $temp['disabled'] = true;
            if (isset($node['router_name'])) {
                $temp['router_name'] = $node['router_name'];
            }
            $temp['necessary_functions'] = $node['necessary_functions'];
            $temp['children'] = $this->SubadminTreeDisable($node['children']);
            $result[] = $temp;
        }

        return $result;
    }

    /**
     * 修改某个子管理员的权限树
     */
    public function UpdSubadminTree()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'subadmin_id' => ['type' => 'integer', 'notNull' => true],
            'subadmin_tree' => ['type' => 'array', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $this->database->update('subadmin', [
            'subadmin_tree' => json_encode($this->payload['subadmin_tree']),
            'subadmin_functions' => json_encode($this->GetPriFunctions($this->payload['subadmin_tree']))
        ], [
            'subadmin_id' => $this->payload['subadmin_id']
        ]);

        return message();
    }
}
