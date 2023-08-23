<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

class Statistics extends Base
{
    function __construct($parm = [])
    {
        parent::__construct($parm);
    }

    /**
     * 获取状态
     * 
     * 负载状态
     * CPU使用率
     * 内存使用率
     */
    public function GetLoadInfo()
    {
        /**
         * ----------1、负载计算开始----------
         */
        $laodavgArray = sys_getloadavg();

        //获取cpu总核数
        $cpuCount = substr_count(file_get_contents('/proc/cpuinfo'), 'model name');

        //一分钟的平均负载 / （cpu总核数 * 2），超过100则为100 不超100为真实值取整
        $percent = round($laodavgArray[0] / ($cpuCount * 2) * 100, 1);
        if ($percent > 100) {
            $percent = 100;
        }

        $data['load'] = [
            'cpuLoad15Min' => $laodavgArray[2],
            'cpuLoad5Min' => $laodavgArray[1],
            'cpuLoad1Min' => $laodavgArray[0],
            'percent' => $percent,
            'color' => funGetColor($percent)['color'],
            'title' => funGetColor($percent)['title']
        ];

        /**
         * ----------2、cpu利率用开始----------
         */
        // 获取第一次采样的 CPU 统计信息
        $procStat1 = file_get_contents('/proc/stat');
        preg_match('/cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $procStat1, $matches1);
        $totalCpuTime1 = array_sum(array_slice($matches1, 1));

        // 等待一段时间
        sleep(1);

        // 获取第二次采样的 CPU 统计信息
        $procStat2 = file_get_contents('/proc/stat');
        preg_match('/cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $procStat2, $matches2);
        $totalCpuTime2 = array_sum(array_slice($matches2, 1));

        // 计算 CPU 利用率
        $totalDiff = $totalCpuTime2 - $totalCpuTime1;
        $idleDiff = $matches2[4] - $matches1[4];
        $cpuAvgUsage = 100 * (1 - ($idleDiff / $totalDiff));
        $cpuAvgUsage = $cpuAvgUsage > 100 ? 100 : $cpuAvgUsage;
        $cpuAvgUsage = $cpuAvgUsage < 0 ? 0 : $cpuAvgUsage;

        /**
         * ----------3、cpu信息开始----------
         */
        $cpuModelArray = [];
        $cpuPhysicalArray = [];
        $cpuProcessor = 0;
        $physicalId = -1;
        $proc_cpuinfo_array = explode("\n", file_get_contents('/proc/cpuinfo'));
        foreach ($proc_cpuinfo_array as $value) {
            if (strstr($value, 'model name')) {
                $modelName = trim(substr($value, strpos($value, ':') + 1));
                if (!in_array($modelName, $cpuModelArray)) {
                    $cpuModelArray[] = $modelName;
                }
            } elseif (strstr($value, 'physical id')) {
                $physicalId = trim(substr($value, strpos($value, ':') + 1));
                $cpuPhysicalArray[$physicalId] = 0;
            } elseif (strstr($value, 'cpu cores')) {
                $cpuPhysicalArray[$physicalId] = intval(trim(substr($value, strpos($value, ':') + 1)));
            } elseif (strstr($value, 'processor')) {
                $cpuProcessor++;
            }
        }

        //物理cpu个数
        $cpuPhysical = count(array_keys($cpuPhysicalArray));

        //总物理核心数 = 每个物理cpu的物理核心数相加
        $cpuCore = array_sum(array_values($cpuPhysicalArray));

        $data['cpu'] = [
            'percent' => round($cpuAvgUsage, 1),
            'cpu' => $cpuModelArray,
            'cpuPhysical' => $cpuPhysical, //物理CPU个数
            'cpuCore' => $cpuCore, //物理CPU的总核心数
            'cpuProcessor' => $cpuProcessor, //物理CPU的线程总数/逻辑核心总数
            'color' => funGetColor($cpuAvgUsage)['color']
        ];

        /**
         * ----------4、内存计算开始----------
         */
        $meminfo = file_get_contents('/proc/meminfo');

        preg_match_all('/^([a-zA-Z()_0-9]+)\s*\:\s*([\d\.]+)\s*([a-zA-z]*)$/m', $meminfo, $meminfos);

        $meminfos = array_combine($meminfos[1], $meminfos[2]);
        $memTotal = (int)$meminfos['MemTotal'];
        $memUsed = $memTotal - (int)$meminfos['MemFree'] - (int)$meminfos['Cached'] - (int)$meminfos['Buffers'] -  (int)$meminfos['SReclaimable'];
        $memFree = $memTotal - $memUsed;

        $percent = round($memUsed / $memTotal * 100, 1);

        $data['mem'] = [
            'memTotal' => round($memTotal / 1024),
            'memUsed' => round($memUsed / 1024),
            'memFree' => round($memFree / 1024),
            'percent' => $percent,
            'color' => funGetColor($percent)['color']
        ];

        return message(200, 1, '成功', $data);
    }

    /**
     * 获取磁盘信息
     */
    public function GetDiskInfo()
    {
        $diskArray = [];

        $diskStats = file_get_contents('/proc/mounts');
        $diskLines = explode("\n", $diskStats);

        $mountedPoints = [];

        foreach ($diskLines as $line) {
            if (!empty($line) && strpos($line, '/') === 0) {
                $diskInfo = explode(" ", $line);
                $mountedOn = trim($diskInfo[1]);
                $filesystem = trim($diskInfo[0]);

                if (!in_array($filesystem, $mountedPoints)) {
                    $mountedPoints[] = $filesystem;
                    $diskUsage = $this->GetDiskUsage($mountedOn);
                    if ($diskUsage) {
                        $diskArray[] = [
                            'fileSystem' => $filesystem,
                            'mountedOn' => $mountedOn,
                            'size' => $diskUsage['size'],
                            'used' => $diskUsage['used'],
                            'avail' => $diskUsage['avail'],
                            'percent' => $diskUsage['percent'],
                            'color' => funGetColor($diskUsage['percent'])['color']
                        ];
                    }
                }
            }
        }


        return message(200, 1, '成功', $diskArray);
    }

    /**
     * 获取磁盘信息
     */
    private function GetDiskUsage($path)
    {
        $diskTotalSpace = disk_total_space($path);
        $diskFreeSpace = disk_free_space($path);

        if ($diskTotalSpace == 0) {
            return null;
        }

        $reservedSpace = $this->getReservedSpace($path);

        $diskUsage = $diskTotalSpace - $diskFreeSpace - $reservedSpace;

        $totalSize = funFormatSize($diskTotalSpace);
        $used = funFormatSize($diskUsage);
        $free = funFormatSize($diskFreeSpace);
        $percent = round(($diskUsage / $diskTotalSpace) * 100, 1);

        return [
            'size' => $totalSize,
            'used' => $used,
            'avail' => $free,
            'percent' => $percent
        ];
    }

    /**
     * 获取系统保留空间
     * 
     * php5有效
     */
    private function GetReservedSpace($path)
    {
        if (!function_exists('statvfs')) {
            return 0;
        }

        $stat = @statvfs($path);

        if ($stat !== false) {
            $blockSize = $stat['bsize'];
            $blocks = $stat['blocks'];
            $freeBlocks = $stat['bfree'];
            $reservedBlocks = $stat['breserved'];

            $reservedSpace = $reservedBlocks * $blockSize;
            return $reservedSpace;
        }

        return 0;
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
    public function GetStatisticsInfo()
    {
        $os = 'Unknown';
        $versionFiles = [
            '/etc/redhat-release',  // CentOS, RHEL
            '/etc/lsb-release',     // Ubuntu
            '/etc/debian_version',  // Debian
            '/etc/fedora-release',  // Fedora
            '/etc/SuSE-release',    // OpenSUSE
            '/etc/arch-release'     // Arch Linux
        ];
        foreach ($versionFiles as $file) {
            if (file_exists($file)) {
                $os = trim(file_get_contents($file));
                break;
            }
        }

        $aliaseCount = $this->SVNAdmin->GetAliaseInfo($this->authzContent);
        if (is_numeric($aliaseCount)) {
            $aliaseCount = -1;
        } else {
            $aliaseCount = count($aliaseCount);
        }

        $backupCount = 0;
        $files = scandir($this->configSvn['backup_base_path']);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (!is_dir($this->configSvn['backup_base_path'] . '/' . $file)) {
                    $backupCount++;
                }
            }
        }

        return message(200, 1, '成功', [
            'os' => trim($os),

            'repCount' => $this->database->count('svn_reps'),
            'repSize' => funFormatSize($this->database->sum('svn_reps', 'rep_size')),

            'backupCount' => $backupCount,
            'backupSize' => funFormatSize(funGetDirSizeDu($this->configSvn['backup_base_path'])),

            'logCount' => $this->database->count('logs', ['log_id[>]' => 0]),

            'adminCount' => $this->database->count('admin_users'),
            'subadminCount' => $this->database->count('subadmin'),
            'userCount' => $this->database->count('svn_users'),
            'groupCount' => $this->database->count('svn_groups'),
            'aliaseCount' => $aliaseCount,
        ]);
    }
}
