<?php

/*
 * 与计划任务操作相关的方法的封装
 */

class Crontab extends Controller {
    /*
     * 注意事项：
     * 1、所有的控制器都要继承基类控制器：Controller
     * 2、基类控制器中包含：数据库连接对象、守护进程通信对象、视图层对象、公共函数等，继承后可以直接使用基类的变量和对象
     * 
     * 用法：
     * 1、使用父类的变量：$this->xxx
     * 2、使用父类的成员函数：parent::yyy()
     * 3、使用父类的非成员函数，直接用即可：zzz() 
     * 4、
     */

    private $svn_repository_path;
    private $backup_path;
    private $Config;

    function __construct() {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
        $this->Config = new Config();

        $this->svn_repository_path = $this->Config->Get("SVN_REPOSITORY_PATH");
        $this->backup_path = $this->Config->Get("BACKUP_PATH");
    }

    //添加计划任务
    function AddCrontab($requestPayload) {
        $backup_type = $requestPayload['backup_type'];
        $cycle_type = $requestPayload['cycle_type'];
        $week = $requestPayload['week'];
        $hour = $requestPayload['hour'];
        $minute = $requestPayload['minute'];
        $repository_name = $requestPayload['repository_name'];
        $crontab_count = $requestPayload['crontab_count'];

        //检查参数
        if (empty($backup_type) || empty($cycle_type) || empty($repository_name) || empty($crontab_count)) {
            $data['status'] = 0;
            $data['message'] = '添加失败 参数不完整';
            return $data;
        }

        //计划任务文件路径
        $cron_path = "/var/spool/cron/root";
        //检查计划任务文件是否存在
        if (!file_exists($cron_path)) {
            RequestReplyExec("touch $cron_path");
        }

        //构造脚本文件标识字符串
        $sign = time() . rand();

        //构造脚本文件路径
        $shell_path = BASE_PATH . '/data/crond/' . $sign;
        RequestReplyExec("touch $shell_path");
        RequestReplyExec("chmod 755 $shell_path");

        //获取执行周期
        $cycle = $this->EnCrontabFormat($cycle_type, $week, $hour, $minute);

        //shell脚本内容
        $shell_content = "";

        //根据备份类型构造脚本
        if ($backup_type == "dump") {
            //构造脚本文件内容
            $path_1 = $this->svn_repository_path . '/' . $repository_name;
            $path_2 = $this->backup_path . '/' . $repository_name . '_' . $sign . '.gz';
            $shell_content = <<<shell
#!/bin/bash
/usr/bin/mkdir -p $this->backup_path
/usr/bin/svnadmin dump -q $path_1 | /usr/bin/gzip > $path_2

shell;
        } else if ($backup_type == "hotcopy") {
            $shell_content = <<<shell
#!/bin/bash
/usr/bin/mkdir $this->backup_path
/usr/bin/svnadmin dump -q $path_1 | /usr/bin/gzip > $path_2

shell;
        } else {
            RequestReplyExec("rm -f $shell_path");
            $data['status'] = 0;
            $data['message'] = '失败 备份类型错误';
            return $data;
        }

        //向脚本文件并写入内容
        RequestReplyExec("echo '$shell_content' > $shell_path");

        //将周期+脚本文件路径以追加方式写入计划任务文件 /var/spool/cron/root
        //$content = $cycle . ' bash ' . $shell_path;
        $content = $cycle . ' bash ' . $shell_path;
        RequestReplyExec("echo '$content' >> $cron_path");
        RequestReplyExec("systemctl restart crond");

        //将信息写入数据库表
        $this->database_medoo->insert("crontab", [
            "backup_type" => $backup_type,
            "cycle_type" => $cycle_type,
            "week" => $week,
            "hour" => $hour,
            "minute" => $minute,
            "repository_name" => $repository_name,
            "crontab_count" => $crontab_count,
            "sign" => $sign
        ]);

        $data['status'] = 1;
        $data['message'] = '添加计划任务成功';
        return $data;
    }

    //删除计划任务
    function DeleteCrontab($requestPayload) {
        $sign = $requestPayload["sign"];
        //从计划任务文件删除
        RequestReplyExec("sed -i '/$sign/d' /var/spool/cron/root");
        //从web路径删除
        RequestReplyExec("rm -f " . BASE_PATH . '/data/crond/' . $sign);
        //从数据库删除
        $this->database_medoo->delete("crontab", [
            "AND" => [
                "sign" => $sign,
            ]
        ]);
        $data['status'] = 1;
        $data['message'] = '删除成功';
        return $data;
    }

    //获取计划任务列表
    function GetCrontabList($requestPayload) {
        $pageSize = trim($requestPayload['pageSize']);
        $currentPage = trim($requestPayload['currentPage']);

        //分页处理
        $begin = $pageSize * ($currentPage - 1);

        $list = $this->database_medoo->select("crontab", [
            "id",
            "backup_type",
            "cycle_type",
            "week",
            "hour",
            "minute",
            "repository_name",
            "crontab_count",
            "sign"
                ], [
            "ORDER" => ["sign" => "DESC"],
            "LIMIT" => [$begin, $pageSize]
        ]);

        //处理内容
        foreach ($list as $key => $value) {
            $list[$key]["crontab_name"] = $value["backup_type"] . '-方式备份SVN仓库-' . $value["repository_name"];
            $list[$key]["crontab_cycle"] = $this->EnTimeFromat($value["cycle_type"]);
            $list[$key]["crontab_time"] = $this->DeCrontabFormat($value["cycle_type"], $value["week"], $value["hour"], $value["minute"]);
            $list[$key]["crontab_count"] = '保存最新' . $value["crontab_count"] . '份';
        }

        //计算数量
        $total = $this->database_medoo->count("crontab");

        //处理自增的id
        $i = 0;
        foreach ($list as $key => $value) {
            $list[$key]["id"] = $i + $begin;
            $i++;
        }

        $data['status'] = 1;
        $data['message'] = '获取计划列表成功';
        $data['data'] = $list;
        $data['total'] = $total;
        return $data;
    }

    //立即执行计划任务
    function StartCrontab() {
        
    }

    //格式化
    private function EnCrontabFormat($cycle_type, $week, $hour, $minute) {
        $content = "";
        switch ($cycle_type) {
            case 'weekly': {
                    $content = $content = sprintf("%s %s * * %s", $minute, $hour, $week);
                };
                break;
            case 'daily': {
                    $content = $content = sprintf("%s %s * * *", $minute, $hour);
                };
                break;
            case 'hourly': {
                    $content = $content = sprintf("%s * * * *", $minute);
                };
                break;
        }
        return $content;
    }

    //格式化
    private function DeCrontabFormat($cycle_type, $week, $hour, $minute) {
        $content = "";
        switch ($cycle_type) {
            case 'weekly': {
                    $content = "每周" . $week . "的" . $hour . "时" . $minute . "分";
                };
                break;
            case 'daily': {
                    $content = "每天的" . $hour . "时" . $minute . "分";
                };
                break;
            case 'hourly': {
                    $content = "每小时的" . $minute . "分";
                };
                break;
        }
        return $content;
    }

    //格式化
    private function EnTimeFromat($cycle_type) {
        $content = "";
        switch ($cycle_type) {
            case 'weekly': {
                    $content = "每周";
                };
                break;
            case 'daily': {
                    $content = "每天";
                };
                break;
            case 'hourly': {
                    $content = "每小时";
                };
                break;
        }
        return $content;
    }

}
