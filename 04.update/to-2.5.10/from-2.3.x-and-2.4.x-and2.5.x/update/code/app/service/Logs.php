<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

class Logs extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 获取日志列表
     */
    public function GetLogList()
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
            'LIMIT' => [$begin, $pageSize],
            'ORDER' => [
                'log_add_time' => 'DESC'
            ]
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
    public function DelLogs()
    {
        $this->database->delete('logs', [
            'log_id[>]' => 0
        ]);

        return message();
    }

    /**
     * 写入日志
     */
    public function InsertLog($log_type_name = '', $log_content = '', $log_add_user_name = '')
    {
        $this->database->insert('logs', [
            'log_type_name' => $log_type_name,
            'log_content' => $log_content,
            'log_add_user_name' => $log_add_user_name,
            'log_add_time' => date('Y-m-d H:i:s')
        ]);
    }
}
