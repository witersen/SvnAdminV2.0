<?php

function detect()
{
    /**
     * ----------负载计算开始----------
     */
    $laodavg = shell_exec("cat /proc/loadavg | awk '{print $1,$2,$3}'");
    $laodavgArray = explode(' ', $laodavg);

    //获取CPU15分钟前到现在的负载平均值
    $cpuLoad15Min = (float)trim($laodavgArray[2]);

    //获取CPU5分钟前到现在的负载平均值
    $cpuLoad5Min = (float)trim($laodavgArray[1]);

    //获取CPU1分钟前到现在的负载平均值
    $cpuLoad1Min = (float)trim($laodavgArray[0]);

    //获取cpu总核数
    $cpuCount  = shell_exec('grep -c "model name" /proc/cpuinfo');
    $cpuCount = (int)trim($cpuCount);

    //一分钟的平均负载 / （cpu总核数 * 2），超过100则为100 不超100为真实值取整
    $percent = round($cpuLoad1Min / ($cpuCount * 2) * 100, 1);
    if ($percent > 100) {
        $percent = 100;
    }

    /**
     * ----------负载计算结束----------
     */
    $data['load'] = $percent;

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
    $topResult = shell_exec('top -b -n 1 | grep Cpu');
    preg_match('/ni,(.*?)id/', $topResult, $matches);
    $id = 100 - (float)trim($matches[1]);

    //cpu型号
    $cpuModelArray = [];
    $cpuModelName = shell_exec("cat /proc/cpuinfo | grep 'model name' | uniq");
    $explodeArray = explode("\n", trim($cpuModelName));
    foreach ($explodeArray as $value) {
        if (trim($value) != '') {
            $tempArray = explode(':', $value);
            array_push($cpuModelArray, trim($tempArray[1]));
        }
    }

    //物理cpu个数
    $cpuPhysical = shell_exec("cat /proc/cpuinfo | grep 'physical id' | sort -u | wc -l");
    $cpuPhysical = (int)trim($cpuPhysical);

    //每个物理cpu的物理核心数
    $cpuPhysicalCore = shell_exec("cat /proc/cpuinfo | grep 'cpu cores' | wc -l");
    $cpuPhysicalCore = (int)trim($cpuPhysicalCore);

    //总物理核心数 = 物理cpu个数 * 每个物理cpu的物理核心数（每个物理cpu的物理核心数都一样吗？）
    $cpuCore = $cpuPhysical * $cpuPhysicalCore;

    //逻辑核心总数（线程总数）
    $cpuProcessor = shell_exec("cat /proc/cpuinfo | grep 'processor' | wc -l");
    $cpuProcessor = (int)trim($cpuProcessor);

    /**
     * ----------cpu计算结束----------
     */
    $data['cpu'] = round($id, 1);

    /**
     * ----------内存计算开始----------
     */
    /**
     * MemTotal 总内存
     * MemFree 空闲内存
     * MemAvailable 可用内存（MemFree + 可回收的内存），系统中有些内存虽然已被使用但是可以回收，比如cache、buffer、slab都有一部分可以回收
     */
    //物理内存总量
    $memTotal = shell_exec("cat /proc/meminfo | grep 'MemTotal' | awk '{print $2}'");
    $memTotal = (int)trim($memTotal);

    //操作系统可用内存总量（没有使用空闲内存）
    $memFree =  shell_exec("cat /proc/meminfo | grep 'MemAvailable' | awk '{print $2}'");
    $memFree = (int)trim($memFree);

    //操作系统已使用内存总量
    $memUsed =  $memTotal - $memFree;

    //内存使用率
    $percent = round($memUsed / $memTotal * 100, 1);

    /**
     * ----------内存计算结束----------
     */
    $data['mem'] = $percent;

    print_r($data);
}

for (;;) {
    detect();
    sleep(1);
}
