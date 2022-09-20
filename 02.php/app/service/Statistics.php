<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-08-28 18:39:06
 * @Description: QQ:1801168257
 */

namespace app\service;

class Statistics extends Base
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取状态
     * 
     * 负载状态
     * CPU使用率
     * 内存使用率
     */
    public function GetSystemStatus()
    {
        /**
         * ----------负载计算开始----------
         */
        $laodavg = funShellExec("cat /proc/loadavg | awk '{print $1,$2,$3}'");
        $laodavg = $laodavg['result'];
        $laodavgArray = explode(' ', $laodavg);

        //获取CPU15分钟前到现在的负载平均值
        $cpuLoad15Min = (float)trim($laodavgArray[2]);

        //获取CPU5分钟前到现在的负载平均值
        $cpuLoad5Min = (float)trim($laodavgArray[1]);

        //获取CPU1分钟前到现在的负载平均值
        $cpuLoad1Min = (float)trim($laodavgArray[0]);

        //获取cpu总核数
        $cpuCount  = funShellExec('grep -c "model name" /proc/cpuinfo');
        $cpuCount = $cpuCount['result'];
        $cpuCount = (int)trim($cpuCount);

        //一分钟的平均负载 / （cpu总核数 * 2），超过100则为100 不超100为真实值取整
        $percent = round($cpuLoad1Min / ($cpuCount * 2) * 100, 1);
        if ($percent > 100) {
            $percent = 100;
        }

        /**
         * ----------负载计算结束----------
         */
        $data['load'] = [
            'cpuLoad15Min' => $cpuLoad15Min,
            'cpuLoad5Min' => $cpuLoad5Min,
            'cpuLoad1Min' => $cpuLoad1Min,
            'percent' => $percent,
            'color' => FunGetColor($percent)['color'],
            'title' => FunGetColor($percent)['title']
        ];

        /**
         * ----------cpu计算开始----------
         */
        /**
         * cpu使用率
         * 
         * %Cpu(s):  0.0 us,  3.2 sy,  0.0 ni, 96.8 id,  0.0 wa,  0.0 hi,  0.0 si,  0.0 st
         * 
         * us user CPU time     用户空间占用CPU百分比
         * sy system CPU time   内核空间占用CPU百分比
         * ni nice CPU          用户进程空间内改变过优先级的进程占用CPU百分比 
         * id idle              空闲CPU百分比
         * wa iowait            等待输入输出的CPU时间百分比
         * hi hardware          硬件中断
         * si software          软件中断 
         * st steal             实时
         */
        $topResult = funShellExec('top -b -n 1 | grep Cpu');
        $topResult = $topResult['result'];
        preg_match('/ni,(.*?)id/', $topResult, $matches);
        $id = 100 - (float)trim($matches[1]);

        //cpu型号
        $cpuModelArray = [];
        $cpuModelName = funShellExec("cat /proc/cpuinfo | grep 'model name' | uniq");
        $cpuModelName = $cpuModelName['result'];
        $explodeArray = explode("\n", trim($cpuModelName));
        foreach ($explodeArray as $value) {
            if (trim($value) != '') {
                $tempArray = explode(':', $value);
                array_push($cpuModelArray, trim($tempArray[1]));
            }
        }

        //物理cpu个数
        $cpuPhysical = funShellExec("cat /proc/cpuinfo | grep 'physical id' | sort -u | wc -l");
        $cpuPhysical = $cpuPhysical['result'];
        $cpuPhysical = (int)trim($cpuPhysical);

        //每个物理cpu的物理核心数
        $cpuPhysicalCore = funShellExec("cat /proc/cpuinfo | grep 'cpu cores' | wc -l");
        $cpuPhysicalCore = $cpuPhysicalCore['result'];
        $cpuPhysicalCore = (int)trim($cpuPhysicalCore);

        //总物理核心数 = 物理cpu个数 * 每个物理cpu的物理核心数（每个物理cpu的物理核心数都一样吗？）
        $cpuCore = $cpuPhysical * $cpuPhysicalCore;

        //逻辑核心总数（线程总数）
        $cpuProcessor = funShellExec("cat /proc/cpuinfo | grep 'processor' | wc -l");
        $cpuProcessor = $cpuProcessor['result'];
        $cpuProcessor = (int)trim($cpuProcessor);

        /**
         * ----------cpu计算结束----------
         */
        $data['cpu'] = [
            'percent' => round($id, 1),
            'cpu' => $cpuModelArray,
            'cpuPhysical' => $cpuPhysical,
            'cpuPhysicalCore' => $cpuPhysicalCore,
            'cpuCore' => $cpuCore,
            'cpuProcessor' => $cpuProcessor,
            'color' => FunGetColor($id)['color']
        ];

        /**
         * ----------内存计算开始----------
         */
        /**
         * MemTotal 总内存
         * MemFree 空闲内存
         * MemAvailable 可用内存（MemFree + 可回收的内存），系统中有些内存虽然已被使用但是可以回收，比如cache、buffer、slab都有一部分可以回收
         */
        //物理内存总量
        $memTotal = funShellExec("cat /proc/meminfo | grep 'MemTotal' | awk '{print $2}'");
        $memTotal = $memTotal['result'];
        $memTotal = (int)trim($memTotal);

        //操作系统可用内存总量（没有使用空闲内存）
        $memFree =  funShellExec("cat /proc/meminfo | grep 'MemAvailable' | awk '{print $2}'");
        $memFree = $memFree['result'];
        $memFree = (int)trim($memFree);

        //操作系统已使用内存总量
        $memUsed =  $memTotal - $memFree;

        //内存使用率
        $percent = round($memUsed / $memTotal * 100, 1);

        /**
         * ----------内存计算结束----------
         */
        $data['mem'] = [
            'memTotal' => round($memTotal / 1024),
            'memUsed' => round($memUsed / 1024),
            'memFree' => round($memFree / 1024),
            'percent' => $percent,
            'color' => FunGetColor($percent)['color']
        ];

        return message(200, 1, '成功', $data);
    }

    /**
     * 获取硬盘
     * 
     * 获取硬盘数量和每个硬盘的详细信息
     */
    public function GetDisk()
    {
        $rs = funShellExec('df -lh | grep -E "^(/)"');
        $rs = $rs['result'];

        //将多个连续的空格换为一个
        $result = preg_replace("/\s{2,}/", ' ', $rs);

        //多个硬盘
        $diskArray = explode("\n", $result);

        $data = [];

        //处理
        foreach ($diskArray as $value) {
            if (trim($value) != '') {
                $diskInfo = explode(" ", $value);
                array_push($data, [
                    'fileSystem' => $diskInfo[0],
                    'size' => $diskInfo[1],
                    'used' => $diskInfo[2],
                    'avail' => $diskInfo[3],
                    'percent' => (int)str_replace('%', '', $diskInfo[4]),
                    'mountedOn' => $diskInfo[5],
                    'color' => FunGetColor((int)str_replace('%', '', $diskInfo[4]))['color']
                ]);
            }
        }

        return message(200, 1, '成功', $data);
    }

    /**
     * 获取统计
     * 
     * 操作系统类型
     * 仓库占用体积
     * SVN仓库数量
     * SVN用户数量
     * SVN分组数量
     * 计划任务数量
     * 运行日志数量
     */
    public function GetSystemAnalysis()
    {
        //操作系统类型和版本
        if (file_exists('/etc/redhat-release')) {
            $os = funShellExec("cat /etc/redhat-release");
            $os = $os['result'];
            // $os = file_get_contents('/etc/redhat-release');
        } else if (file_exists('/etc/lsb-release')) {
            $os = funShellExec("cat /etc/lsb-release");
            $os = $os['result'];
            // $os = file_get_contents('/etc/lsb-release');
        } else {
            $os = 'Linux';
        }

        //仓库占用体积
        $repSize = FunFormatSize(FunGetDirSizeDu($this->config_svn['rep_base_path']));

        //备份占用体积
        $backupSize = FunFormatSize(FunGetDirSizeDu($this->config_svn['backup_base_path']));

        //SVN仓库数量
        $repCount = count($this->SVNAdminRep->GetSimpleRepList());

        //SVN用户数量
        $userCount = $this->SVNAdmin->GetUserInfo($this->passwdContent);
        if (is_numeric($userCount)) {
            if ($userCount == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($userCount == 710) {
                return message(200, 0, '用户不存在');
            } else {
                return message(200, 0, "错误码$userCount");
            }
        }
        $userCount  = count($userCount);

        //SVN分组数量
        $groupCount = $this->SVNAdmin->GetGroupInfo($this->authzContent);
        if (is_numeric($groupCount)) {
            if ($groupCount == 612) {
                return message(200, 0, '文件格式错误(不存在[groups]标识)');
            } else if ($groupCount == 720) {
                return message(200, 0, '指定的分组不存在');
            } else {
                return message(200, 0, "错误码$groupCount");
            }
        }
        $groupCount = count($groupCount);

        //运行日志数量
        $logCount = $this->database->count('logs', ['log_id[>]' => 0]);

        return message(200, 1, '成功', [
            'os' => trim($os),
            'repSize' => $repSize,
            'repCount' => $repCount,
            'repUser' => $userCount,
            'repGroup' => $groupCount,
            'logCount' => $logCount,
            'backupSize' => $backupSize
        ]);
    }
}
