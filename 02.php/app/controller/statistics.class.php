<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-27 18:23:46
 * @Description: QQ:1801168257
 */

// use SVNAdmin\SVN\Rep;
// use SVNAdmin\SVNRep\SVNRep;

/**
 * 信息统计类
 */
class statistics extends controller
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
     * 获取状态
     * 
     * 负载状态
     * CPU使用率
     * 内存使用率
     */
    function GetSystemStatus()
    {
        /**
         * ----------负载计算开始----------
         */
        $laodavg = FunShellExec("cat /proc/loadavg | awk '{print $1,$2,$3}'");
        $laodavgArray = explode(' ', $laodavg);

        //获取CPU15分钟前到现在的负载平均值
        $cpuLoad15Min = (float)trim($laodavgArray[2]);

        //获取CPU5分钟前到现在的负载平均值
        $cpuLoad5Min = (float)trim($laodavgArray[1]);

        //获取CPU1分钟前到现在的负载平均值
        $cpuLoad1Min = (float)trim($laodavgArray[0]);

        //获取cpu总核数
        $cpuCount  = FunShellExec('grep -c "model name" /proc/cpuinfo');
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
        $topResult = FunShellExec('top -b -n 1 | grep Cpu');
        preg_match('/ni,(.*?)id/', $topResult, $matches);
        $id = 100 - (float)trim($matches[1]);

        //cpu型号
        $cpuModelArray = [];
        $cpuModelName = FunShellExec("cat /proc/cpuinfo | grep 'model name' | uniq");
        $explodeArray = explode("\n", trim($cpuModelName));
        foreach ($explodeArray as $value) {
            if (trim($value) != '') {
                $tempArray = explode(':', $value);
                array_push($cpuModelArray, trim($tempArray[1]));
            }
        }

        //物理cpu个数
        $cpuPhysical = FunShellExec("cat /proc/cpuinfo | grep 'physical id' | sort -u | wc -l");
        $cpuPhysical = (int)trim($cpuPhysical);

        //每个物理cpu的物理核心数
        $cpuPhysicalCore = FunShellExec("cat /proc/cpuinfo | grep 'cpu cores' | wc -l");
        $cpuPhysicalCore = (int)trim($cpuPhysicalCore);

        //总物理核心数 = 物理cpu个数 * 每个物理cpu的物理核心数（每个物理cpu的物理核心数都一样吗？）
        $cpuCore = $cpuPhysical * $cpuPhysicalCore;

        //逻辑核心总数（线程总数）
        $cpuProcessor = FunShellExec("cat /proc/cpuinfo | grep 'processor' | wc -l");
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
        $memTotal = FunShellExec("cat /proc/meminfo | grep 'MemTotal' | awk '{print $2}'");
        $memTotal = (int)trim($memTotal);

        //操作系统可用内存总量（没有使用空闲内存）
        $memFree =  FunShellExec("cat /proc/meminfo | grep 'MemAvailable' | awk '{print $2}'");
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

        FunMessageExit(200, 1, '成功', $data);
    }

    /**
     * 获取硬盘
     * 
     * 获取硬盘数量和每个硬盘的详细信息
     */
    function GetDisk()
    {
        $rs = FunShellExec('df -lh | grep -E "^(/)"');

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

        FunMessageExit(200, 1, '成功', $data);
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
    function GetSystemAnalysis()
    {
        //操作系统类型和版本
        $os = FunShellExec("cat /etc/redhat-release");

        //仓库占用体积
        $repSize = FunFormatSize(FunGetDirSizeDu(SVN_REPOSITORY_PATH));

        //备份占用体积
        $backupSize = FunFormatSize(FunGetDirSizeDu(SVN_BACHUP_PATH));

        //SVN仓库数量
        $repCount = count(\SVNAdmin\SVN\Rep::GetSimpleRepList());

        //SVN用户数量
        $userCount = \SVNAdmin\SVN\User::GetSvnUserList($this->globalPasswdContent);
        if ($userCount === '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        $userCount  = count($userCount);

        //SVN分组数量
        $groupCount = \SVNAdmin\SVN\Group::GetSvnGroupList($this->globalAuthzContent);
        if ($userCount === '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[groups]标识)');
        }
        $groupCount = count($groupCount);

        //运行日志数量
        $logCount = $this->database->count('logs', ['log_id[>]' => 0]);

        FunMessageExit(200, 1, '成功', [
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
