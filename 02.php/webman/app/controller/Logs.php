<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 14:35:35
 * @Description: QQ:1801168257
 */

namespace app\controller;

use support\Request;

class Logs extends Core
{
    /**
     * 获取日志列表
     */
    public function GetLogList(Request $request)
    {
        $pageSize = $this->payload['pageSize'];
        $currentPage = $this->payload['currentPage'];
        $searchKeyword = trim($this->payload['searchKeyword']);

        //分页
        $begin = $pageSize * ($currentPage - 1);

        $list = $this->database->select('logs', [
            'log_id',
            'log_type_name',
            'log_content',
            'log_add_user_name',
            'log_add_time',
        ], [
            'AND' => [
                'OR' => [
                    'log_type_name[~]' => $searchKeyword,
                    'log_content[~]' => $searchKeyword,
                    'log_add_user_name[~]' => $searchKeyword,
                    'log_add_time[~]' => $searchKeyword,
                ],
            ],
            'LIMIT' => [$begin, $pageSize]
        ]);

        $total = $this->database->count('logs', [
            'log_id'
        ], [
            'AND' => [
                'OR' => [
                    'log_type_name[~]' => $searchKeyword,
                    'log_content[~]' => $searchKeyword,
                    'log_add_user_name[~]' => $searchKeyword,
                    'log_add_time[~]' => $searchKeyword,
                ],
            ]
        ]);

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 清空日志
     */
    public function ClearLogs(Request $request)
    {
        $this->database->delete('logs', [
            'log_id[>]' => 0
        ]);

        return message();
    }
}
