<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

class Tasks extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 获取后台任务实时日志
     *
     * @return void
     */
    public function GetTaskRun()
    {
        //排队数量
        $total = $this->database->count('tasks', [
            'task_id'
        ], [
            'OR' => [
                'task_status' => [
                    1,
                    2
                ]
            ],
        ]);

        //获取当前执行中的任务列表
        $result = $this->database->get('tasks', [
            'task_id [Int]',
            'task_name',
            // 'task_status',
            // 'task_cmd',
            // 'task_unique',
            'task_log_file',
            // 'task_optional',
            // 'task_create_time',
            // 'task_update_time'
        ], [
            'task_status' => 2
        ]);
        if (empty($result)) {
            return message(200, 1, '成功', [
                'task_name' => '',
                'task_running' => false,
                'task_log' => '',
                'task_queue_count' => $total
            ]);
        }

        //获取任务的日志路径
        $file = $result['task_log_file'];
        if (!file_exists($file)) {
            return message(200, 0, sprintf('日志文件[%s]不存在或不可读', $file));
        }

        //读取日志返回
        $filesize = filesize($file) / 1024 / 1024;
        return message(200, 1, '成功', [
            'task_name' => $result['task_name'],
            'task_running' => true,
            'task_log' => $filesize > 10 ? sprintf('日志文件[%s]体积超过10M需手动查看', $file) : file_get_contents($file),
            'task_queue_count' => $total
        ]);
    }

    /**
     * 获取后台任务队列
     *
     * @return void
     */
    public function GetTaskQueue()
    {
        $list = $this->database->select('tasks', [
            'task_id [Int]',
            'task_name',
            'task_status [Int]',
            'task_cmd',
            'task_unique',
            'task_log_file',
            'task_optional',
            'task_create_time',
            'task_update_time'
        ], [
            'OR' => [
                'task_status' => [
                    1,
                    2
                ]
            ],
            'ORDER' => [
                'task_id'  => 'ASC'
            ]
        ]);

        return message(200, 1, '成功', [
            'data' => $list,
        ]);
    }

    /**
     * 获取后台任务执行历史
     *
     * @return void
     */
    public function GetTaskHistory()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'pageSize' => ['type' => 'integer', 'notNull' => true],
            'currentPage' => ['type' => 'integer', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $pageSize = $this->payload['pageSize'];
        $currentPage = $this->payload['currentPage'];

        //分页
        $begin = $pageSize * ($currentPage - 1);

        $list = $this->database->select('tasks', [
            'task_id [Int]',
            'task_name',
            'task_status',
            'task_cmd',
            'task_unique',
            'task_log_file',
            'task_optional',
            'task_create_time',
            'task_update_time'
        ], [
            'OR' => [
                'task_status' => [
                    3,
                    4,
                    5
                ]
            ],
            'LIMIT' => [$begin, $pageSize],
            'ORDER' => [
                'task_id'  => 'DESC'
            ]
        ]);

        $total = $this->database->count('tasks', [
            'task_id'
        ], [
            'OR' => [
                'task_status' => [
                    3,
                    4,
                    5
                ]
            ],
        ]);

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 获取历史任务日志
     *
     * @return void
     */
    public function GetTaskHistoryLog()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'task_id' => ['type' => 'integer', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $result = $this->database->get('tasks', [
            // 'task_id [Int]',
            'task_name',
            'task_status',
            // 'task_cmd',
            // 'task_unique',
            'task_log_file',
            // 'task_optional',
            // 'task_create_time',
            // 'task_update_time'
        ], [
            'task_id' => $this->payload['task_id']
        ]);
        if (empty($result)) {
            return message(200, 1, '任务不存在');
        }

        //获取任务的日志路径
        $file = $result['task_log_file'];
        if (!file_exists($file)) {
            return message(200, 0, sprintf('日志文件[%s]不存在或不可读', $file));
        }

        //读取日志返回
        return message(200, 1, '成功', [
            'task_name' => $result['task_name'],
            'task_log' => file_get_contents($file),
        ]);
    }

    /**
     * 删除历史执行任务
     *
     * @return void
     */
    public function DelTaskHistory()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'task_id' => ['type' => 'integer', 'notNull' => true],
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $result = $this->database->get('tasks', [
            'task_lof_file'
        ], [
            'task_id' => $this->payload['task_id']
        ]);

        if (!empty($result)) {
            @unlink($result['task_log_file']);
        }

        $this->database->delete('tasks', [
            'task_id' => $this->payload['task_id']
        ]);

        return message();
    }

    /**
     * 停止后台任务
     *
     * @return void
     */
    public function UpdTaskStop()
    {
        //检查表单
        $checkResult = funCheckForm($this->payload, [
            'task_id' => ['type' => 'integer', 'notNull' => true]
        ]);
        if ($checkResult['status'] == 0) {
            return message($checkResult['code'], $checkResult['status'], $checkResult['message'] . ': ' . $checkResult['data']['column']);
        }

        $task_id = $this->payload['task_id'];

        $result = $this->database->get('tasks', [
            'task_id [Int]',
            'task_name',
            'task_status',
            'task_cmd',
            'task_unique',
            'task_log_file',
            'task_optional',
            'task_create_time',
            'task_update_time'
        ], [
            'task_id' => $task_id
        ]);
        if (empty($result)) {
            return message(200, 0, '任务不存在');
        }

        if ($result['task_status'] == 2) {
            // 如果是正在执行的后台任务则通过pid停止

            $result = funShellExec(sprintf("ps aux | grep -v grep | grep %s | awk 'NR==1' | awk '{print $2}'", $result['task_unique']));
            if ($result['code'] != 0) {
                return message(200, 0, '获取进程失败: ' . $result['error']);
            }

            $pid = trim($result['result']);

            if (empty($pid)) {
                $this->database->update('tasks', [
                    'task_status' => 4
                ], [
                    'task_id' => $task_id
                ]);

                return message();
            }

            clearstatcache();
            if (!is_dir("/proc/$pid")) {
                $this->database->update('tasks', [
                    'task_status' => 4
                ], [
                    'task_id' => $task_id
                ]);

                return message();
            }

            $info = funShellExec(sprintf("kill -15 %s && kill -9 %s", trim($result['result']), trim($result['result'])), true);
            if ($info['code'] != 0) {
                return message(200, 0, $info['error']);
            }

            $this->database->update('tasks', [
                'task_status' => 4
            ], [
                'task_id' => $task_id
            ]);

            return message();
        } elseif ($result['task_status'] == 1) {
            // 如果是待执行的后台任务则直接从数据库中标记为已删除
            $result = $this->database->update('tasks', [
                'task_status' => 4
            ], [
                'task_id' => $task_id
            ]);

            return message();
        } elseif ($result['task_status'] == 3) {
            return message();
        } elseif ($result['task_status'] == 4) {
            return message();
        } elseif ($result['task_status'] == 5) {
            return message();
        }

        return message(200, 0, '不支持当前操作');
    }
}
