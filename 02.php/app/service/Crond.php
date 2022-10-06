<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-06 21:37:57
 * @Description: QQ:1801168257
 */

namespace app\service;

class Crond extends Base
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取特殊结构的下拉列表
     *
     * @return void
     */
    public function GetRepList()
    {
        $list = $this->database->select('svn_reps', [
            'rep_name(rep_key)',
            'rep_name',
        ]);

        $list = array_merge([[
            'rep_key' => '-1',
            'rep_name' => '所有仓库'
        ]], $list);

        return message(200, 1, '成功', $list);
    }

    /**
     * 获取任务计划列表
     *
     * @return array
     */
    public function GetCrondList()
    {
        $pageSize = $this->payload['pageSize'];
        $currentPage = $this->payload['currentPage'];
        $searchKeyword = trim($this->payload['searchKeyword']);

        //分页
        $begin = $pageSize * ($currentPage - 1);

        $list = $this->database->select('crond', [
            'crond_id',
            'sign',
            'task_type',
            'task_name',
            'cycle_type',
            'cycle_desc',
            'status',
            'save_count',
            'week',
            'day',
            'hour',
            'minute',
            'notice',
            'last_exec_time',
            'create_time',
        ], [
            'AND' => [
                'OR' => [
                    'task_name[~]' => $searchKeyword,
                    'cycle_desc[~]' => $searchKeyword,
                ],
            ],
            'LIMIT' => [$begin, $pageSize],
            'ORDER' => [
                $this->payload['sortName']  => strtoupper($this->payload['sortType'])
            ]
        ]);

        $total = $this->database->count('crond', [
            'crond_id'
        ], [
            'AND' => [
                'OR' => [
                    'task_name[~]' => $searchKeyword,
                    'cycle_desc[~]' => $searchKeyword,
                ],
            ],
        ]);

        foreach ($list as $key => $value) {
            $list[$key]['status'] = $value['status'] == 1 ? true : false;
        }

        return message(200, 1, '成功', [
            'data' => $list,
            'total' => $total
        ]);
    }

    /**
     * 设置任务计划
     *
     * @return array
     */
    public function SetCrond()
    {
        //todo 检查crond服务有无开启
        //todo 上次执行时间

        if (!isset($this->payload['cycle'])) {
            return message(200, 0, '参数[cycle]不存在');
        }
        $cycle = $this->payload['cycle'];

        //sign 处理
        $sign = md5(time());

        //notice 处理
        if (in_array('success', (array)$cycle['notice']) && in_array('fail', (array)$cycle['notice'])) {
            $cycle['notice'] = 3;
        } else if (in_array('fail', (array)$cycle['notice'])) {
            $cycle['notice'] = 2;
        } else if (in_array('success', (array)$cycle['notice'])) {
            $cycle['notice'] = 1;
        } else {
            $cycle['notice'] = 0;
        }

        //cycle_desc 和 code 处理
        $code = '';
        $cycle['cycle_desc'] = '';
        switch ($cycle['cycle_type']) {
            case 'minute': //每分钟
                $code = '* * * * *';
                $cycle['cycle_desc'] = "每分钟执行一次";
                break;
            case 'minute_n': //每隔N分钟
                $code = sprintf("*/%s * * * *", $cycle['minute']);
                $cycle['cycle_desc'] = sprintf("每隔%s分钟执行一次", $cycle['minute']);
                break;
            case 'hour': //每小时
                $code = sprintf("%s * * * *", $cycle['minute']);
                $cycle['cycle_desc'] = sprintf("每小时-第%s分钟执行一次", $cycle['minute']);
                break;
            case 'hour_n': //每隔N小时
                $code = sprintf("%s */%s * * *", $cycle['minute'], $cycle['hour']);
                $cycle['cycle_desc'] = sprintf("每隔%s小时-第%s分钟执行一次", $cycle['hour'], $cycle['minute']);
                break;
            case 'day': //每天
                $code = sprintf("%s %s * * *", $cycle['minute'], $cycle['hour']);
                $cycle['cycle_desc'] = sprintf("每天-%s点%s分执行一次", $cycle['hour'], $cycle['minute']);
                break;
            case 'day_n': //每隔N天
                $code = sprintf("%s %s */%s * *", $cycle['minute'], $cycle['hour'], $cycle['day']);
                $cycle['cycle_desc'] = sprintf("每隔%s天-%s点%s分执行一次", $cycle['day'], $cycle['hour'], $cycle['minute']);
                break;
            case 'week': //每周
                $code = sprintf("%s %s * * %s", $cycle['minute'], $cycle['hour'], $cycle['week']);
                $cycle['cycle_desc'] = sprintf("每周%s-%s点%s分执行一次", $cycle['week'], $cycle['hour'], $cycle['minute']);
                break;
            case 'month': //每月
                $code = sprintf("%s %s %s * *", $cycle['minute'], $cycle['hour'], $cycle['day']);
                $cycle['cycle_desc'] = sprintf("每月%s日-%s点%s分执行一次", $cycle['day'], $cycle['hour'], $cycle['minute']);
                break;
            default:
                break;
        }

        //写入 /home/svnadmin/crond/xxx
        if (!is_dir($this->config_svn['crond_base_path'])) {
            funShellExec(sprintf("mkdir -p '%s' && chmod 777 -R '%s'", $this->config_svn['crond_base_path'], $this->config_svn['crond_base_path']));
        }
        $nameCrond = $this->config_svn['crond_base_path'] . $sign;
        $nameCrondLog = $nameCrond . '.log';

        $conCrond = '';
        switch ($cycle['task_type']) {
            case 1: //仓库备份[dump-全量]
                $conCrond = sprintf(
                    "#!/bin/bash
                PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
                export PATH
                startDate=`date +%s`
                echo ----------starTime:[\$startDate]--------------------------------------------
                php %s %s %s
                endDate=`date +%s`
                echo ----------endTime:[\$endDate]--------------------------------------------",
                    "\"%Y-%m-%d %H:%M:%S\"",
                    BASE_PATH . '/server/command.php',
                    $cycle['task_type'],
                    $sign,
                    "\"%Y-%m-%d %H:%M:%S\""
                );
                break;
            case 2: //仓库备份[dump-增量]
                return message(200, 0, '暂未支持的类型');
                break;
            case 3: //仓库备份[hotcopy-全量]
                return message(200, 0, '暂未支持的类型');
                break;
            case 4: //仓库备份[hotcopy-增量]
                return message(200, 0, '暂未支持的类型');
                break;
            case 5: //仓库检查
                return message(200, 0, 'todo');
                break;
            case 6: //shell脚本
                $conCrond = sprintf("#!/bin/bash\nPATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin\nexport PATH\n%s", $cycle['shell']);
                break;
            default:
                return message(200, 0, '暂未支持的类型');
                break;
        }
        file_put_contents($nameCrond, $conCrond);
        // funShellExec(sprintf("chmod 777 '%s' && chmod 777 '%s'", $nameCrond, $nameCrondLog));
        funShellExec(sprintf("chmod 777 '%s'", $nameCrond));

        //crontab -l 获取原有的任务计划列表
        $result = funShellExec('crontab -l');
        $crontabs = trim($result['result']);

        //crontab file 写入新的任务计划列表
        $tempFile = tempnam('/tmp', 'svnadmin_crond_');
        file_put_contents($tempFile, (empty($crontabs) ? '' : $crontabs . "\n") . sprintf("%s %s >> %s 2>&1\n", $code, $nameCrond, $nameCrondLog));
        $result = funShellExec(sprintf("crontab %s", $tempFile));
        @unlink($tempFile);
        if ($result['code'] != 0) {
            @unlink($nameCrond);
            return message(200, 0, $result['error']);
        }

        $this->database->insert('crond', [
            'sign' => $sign,
            'task_type' => $cycle['task_type'],
            'task_name' => $cycle['task_name'], //有机会为空 不可为空
            'cycle_type' => $cycle['cycle_type'],
            'cycle_desc' => $cycle['cycle_desc'], //需要自己根据周期生成语义化描述
            'status' => 1, //启用状态 默认启用
            'save_count' => $cycle['save_count'],
            'rep_name' => json_encode([$cycle['rep_key']]),
            'week' => $cycle['week'],
            'day' => $cycle['day'],
            'hour' => $cycle['hour'],
            'minute' => $cycle['minute'],
            'notice' => $cycle['notice'],
            'code' => $code,
            'shell' => $cycle['shell'],
            'last_exec_time' => '-',
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        return message(200, 1, '成功');
    }

    /**
     * 更新任务计划
     *
     * @return array
     */
    public function UpdCrond()
    {
    }

    /**
     * 修改任务计划状态
     *
     * @return void
     */
    public function UpdCrondStatus()
    {
        if (!isset($this->payload['crond_id'])) {
            return message(200, 0, '参数不完整');
        }

        $result = $this->database->get('crond', '*', [
            'crond_id' => $this->payload['crond_id']
        ]);
        if (empty($result)) {
            return message(200, 0, '任务计划不存在');
        }

        $sign = $result['sign'];
        $code = $result['code'];

        //crontab -l 获取原有的任务计划列表
        $result = funShellExec('crontab -l');
        $crontabs = trim($result['result']);

        if ($this->payload['disable']) {
            //查询标识并删除标识所在行
            $contabArray = explode("\n", $crontabs);
            foreach ($contabArray as $key => $value) {
                if (strstr($value, ' ' . $sign . '.log') || strstr($value, $sign . '.log')) {
                    unset($contabArray[$key]);
                }
            }
            $crontabs = trim(implode("\n", $contabArray));
        } else {
            $nameCrond = $this->config_svn['crond_base_path'] . $sign;
            $nameCrondLog = $nameCrond . '.log';
            $crontabs = (empty($crontabs) ? '' : $crontabs . "\n") . sprintf("%s %s >> %s 2>&1", $code, $nameCrond, $nameCrondLog);
        }

        if (empty($crontabs)) {
            funShellExec('crontab -r');
        } else {
            $tempFile = tempnam('/tmp', 'svnadmin_crond_');
            file_put_contents($tempFile, $crontabs . "\n");
            $result = funShellExec(sprintf("crontab %s", $tempFile));
            @unlink($tempFile);
            if ($result['code'] != 0) {
                return message(200, 0, $result['error']);
            }
        }

        //从数据库修改
        $this->database->update('crond', [
            'status' => $this->payload['disable'] ? 0 : 1
        ], [
            'crond_id' => $this->payload['crond_id']
        ]);

        return message();
    }

    /**
     * 删除任务计划
     *
     * @return array
     */
    public function DelCrond()
    {
        if (!isset($this->payload['crond_id'])) {
            return message(200, 0, '参数不完整');
        }

        $result = $this->database->get('crond', '*', [
            'crond_id' => $this->payload['crond_id']
        ]);
        if (empty($result)) {
            return message(200, 0, '任务计划不存在');
        }
        $sign = $result['sign'];

        //crontab -l 获取原有的任务计划列表
        $result = funShellExec('crontab -l');
        $crontabs = trim($result['result']);

        //查询标识并删除标识所在行
        $contabArray = explode("\n", $crontabs);
        foreach ($contabArray as $key => $value) {
            if (strstr($value, $sign . '.log')) {
                unset($contabArray[$key]);
                break;
            }
        }
        if ($contabArray == explode("\n", $crontabs)) {
            //无改动 删除的为已暂停的记录
        } else {
            $crontabs = trim(implode("\n", $contabArray));
            //crontab file 写入新的任务计划列表
            if (empty($crontabs)) {
                funShellExec('crontab -r');
            } else {
                $tempFile = tempnam('/tmp', 'svnadmin_crond_');
                file_put_contents($tempFile, $crontabs . "\n");
                $result = funShellExec(sprintf("crontab %s", $tempFile));
                @unlink($tempFile);
                if ($result['code'] != 0) {
                    return message(200, 0, $result['error']);
                }
            }
        }

        //从文件删除
        @unlink($this->config_svn['crond_base_path'] . $sign);

        //从数据库删除
        $this->database->delete('crond', [
            'crond_id' => $this->payload['crond_id']
        ]);

        return message();
    }

    /**
     * 获取日志信息
     *
     * @return void
     */
    public function GetLog()
    {
        if (!isset($this->payload['crond_id'])) {
            return message(200, 0, '参数不完整');
        }

        $result = $this->database->get('crond', '*', [
            'crond_id' => $this->payload['crond_id']
        ]);
        if (empty($result)) {
            return message(200, 0, '任务计划不存在');
        }
        $sign = $result['sign'];

        clearstatcache();
        if (file_exists($this->config_svn['crond_base_path'] . $sign . '.log')) {
            return message(200, 1, '成功', file_get_contents($this->config_svn['crond_base_path'] . $sign . '.log'));
        } else {
            return message(200, 1, '成功', '');
        }
    }

    private function Install()
    {
        /**
         * 注意要对 crontab -l 的结果作出提示 用什么用户执行后台程序 任务计划的所属用户就是哪个用户
         * 
         * 还要有任务名称、还要标识哪些是自己项目的任务计划、还有保存数量、还有备份位置(本地磁盘、oss)、日志路径
         * 
         * 需要通过数据库维护任务计划列表 但是如果切换了数据库 就需要执行数据库迁移[yes][需要双向的数据库迁移程序]
         * 或者不通过数据库维护任务计划表 通过json 或者通过文件夹
         * 
         * crontab -l #测试是否安装 crontab
         * 
         * yum install -y cronid # CentOS 上的包名 具体的要做出提示
         * 
         * 展示cron的状态
         * 声明这是用户级的
         * 
         * 写入流程：
         * 生成命令内容
         * 写入内容到文件 /home/svnadmin/crond/xxx
         * crontab -u root -l 获取所有的任务计划列表并写入临时文件 tempfile
         * 将要写入的内容新增到临时文件
         * crontab tempfile
         * 
         * 删除流程：
         * 发送删除的id 数据库根据id找到文件标识
         * crontab -u root -l 获取所有的任务计划列表并写入临时文件 tempfile
         * 删除文件中标识所在行
         * 删除数据库记录
         * crontab tempfile
         * 
         * 修改流程：
         * 发送修改的id + 新内容 数据库根据id找到文件标识 
         * 修改文件
         * 
         * 获取流程：
         * crontab -u root -l 获取所有的任务计划列表 作为部分1
         * 从数据库中拿到暂停的任务列表 作为部分2
         * 将 部分1 + 部分2 返回作为最终数据
         * 
         * 暂停流程：
         * 发送id 数据库根据id找到文件标识
         * crontab -u root -l 获取所有的任务计划列表并写入临时文件 tempfile
         * 删除标识所在行 修改数据库字段
         * crontab tempfile
         * 
         * 取消暂停流程：
         * 发送id 数据库根据id找到文件标识
         * crontab -u root -l 获取所有的任务计划列表并写入临时文件 tempfile
         * 增加内容 修改数据库字段
         * crontab tempfile
         * 
         * 立即执行流程：
         * 发送id 数据库根据id找到文件标识
         * 执行 写入日志
         * 
         */
    }
}
