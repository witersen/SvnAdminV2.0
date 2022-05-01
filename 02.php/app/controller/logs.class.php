<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-26 16:57:13
 * @Description: QQ:1801168257
 */

class logs extends controller
{
    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
    }

    /**
     * 获取日志列表
     */
    function GetLogList()
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

        FunMessageExit(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 清空日志
     */
    function ClearLogs()
    {
        $this->database->delete('logs', [
            'log_id[>]' => 0
        ]);

        FunMessageExit();
    }

    /**
     * 写入日志
     */
    function InsertLog($log_type_name = '', $log_content = '', $log_add_user_name = '')
    {
        $this->database->insert('logs', [
            'log_type_name' => $log_type_name,
            'log_content' => $log_content,
            'log_add_user_name' => $log_add_user_name,
            'log_add_time' => date('Y-m-d H:i:s')
        ]);
    }
}
