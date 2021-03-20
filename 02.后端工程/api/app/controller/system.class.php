<?php

/*
 * 与操作系统相关的方法的封装
 */

class System extends Controller {
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
    }

    //判断操作系统类型
    function GetPlatform() {
        if (!PHP_OS == 'Linux') {
            $data['status'] = 0;
            $data['message'] = '当前操作系统不为Linux';
            return $data;
        }
        if (file_exists('/etc/redhat-release')) {
            $info = file_get_contents('/etc/redhat-release');
            if (!strstr($info, 'CentOS') && (strstr($info, '8.') || strstr($info, '7.'))) {
                $data['status'] = 0;
                $data['message'] = '当前Linux操作系统版本不符合要求';
                return $data;
            }
            $data['status'] = 1;
            $data['message'] = '成功';
            $data['platform'] = 'CentOS';
            return $data;
        } elseif (file_exists('etc/lsb-release')) {
            $info = file_get_contents('etc/lsb-release');
            if (1) {
                $data['status'] = 0;
                $data['message'] = '当前操作系统不受支持';
                return $data;
            }
            $data['status'] = 1;
            $data['message'] = '成功';
            $data['platform'] = 'Ubuntu';
            return $data;
        } else {
            $data['status'] = 0;
            $data['message'] = '当前操作系统不受支持';
            return $data;
        }
    }

    //获取磁盘信息，如果有多块磁盘如何处理，可以只显示某个目录如根目录对应的磁盘容量
    function GetDiskInfo($requestPayload) {
        $info['DiskTotal'] = round(disk_total_space(".") / 1024 / 1024 / 1024, 1);
        $info['DiskFree'] = round(disk_free_space(".") / 1024 / 1024 / 1024, 1);
        $info['DiskUsed'] = round($info['DiskTotal'] - $info['DiskFree'], 1);
        $info['DiskPercent'] = ($info['DiskTotal'] != 0) ? round($info['DiskUsed'] / $info['DiskTotal'] * 100, 1) : 0;

        $data['status'] = 1;
        $data['message'] = '获取磁盘信息成功';
        $data['data'] = $info;
        return $data;
    }

    //获取服务器运行时间
    function GetServerUpTime($requestPayload) {
        $temp = file_get_contents('/proc/uptime');
        $info = explode(" ", $temp);
        $info = trim($info[0]); //系统自启动开始的秒数
        $min = $info / 60;
        $hours = $min / 60;
        $days = floor($hours / 24);
        $hours = floor($hours - ( $days * 24));
        $min = floor($min - ( $days * 60 * 24) - ( $hours * 60));
        $info = $days . "天" . $hours . "小时" . $min . "分钟";

        $data['status'] = 1;
        $data['message'] = '获取服务器运行时间成功';
        $data['data'] = $info;
        return $data;
    }

    //计算CPU使用率
    /*
     * 第一行为CPU总情况 只需要第一行的值
     * cpu 2032004 102648 238344 167130733 758440 1515917878 0
     * 
     * user 2032004 从系统启动开始累计到当前时刻，用户态的CPU时间，不包含nice值为负进程
     * nice 102648 从系统启动开始累计到当前时刻，nice值为负的进程所占用的CPU时间
     * system 238344 从系统启动开始累计到当前时刻，核心时间
     * idle 167130733 从系统启动开始累计到当前时刻，除IO等待时间以外其它等待时间
     * iowait 758440 从系统启动开始累计到当前时刻，IO等待时间
     * irq 1515917878 从系统启动开始累计到当前时刻，硬中断时间
     * softirq 0 从系统启动开始累计到当前时刻，软中断时间
     */
    //GetCPURate GetCPUInfo
    function GetCPURate($requestPayload) {
        //第一次取值
        $array = file('/proc/stat');
        if (!$array)
            return false;
        $array = explode(' ', trim(str_replace('cpu', '', trim($array[0]))));
        $total_time_1 = $array[0] + $array[1] + $array[2] + $array[3] + $array[4] + $array[5] + $array[6];
        $fiee_time_1 = $array[3];

        sleep(1);

        //第二次取值
        $array = file('/proc/stat');
        if (!$array)
            return false;
        $array = explode(' ', trim(str_replace('cpu', '', trim($array[0]))));
        $total_time_2 = $array[0] + $array[1] + $array[2] + $array[3] + $array[4] + $array[5] + $array[6];
        $fiee_time_2 = $array[3];

        //计算
        $result['percent'] = round(round(1.00 - ($fiee_time_2 - $fiee_time_1) / ($total_time_2 - $total_time_1), 2) * 100, 1);

        $data['status'] = 1;
        $data['message'] = '获取CPU信息成功';
        $data['data'] = $result;
        return $data;
    }

    //获取内存信息
    function GetMemInfo($requestPayload) {
        $array = file('/proc/meminfo');
        if (!$array)
            return false;
        foreach ($array as $key => $value) {
            $array[$key] = trim($value);
        }
        foreach ($array as $key => $value) {
            $a = explode(':', $value);
            $k = trim($a[0]);
            $v = trim(str_replace('kB', '', trim($a[1])));
            $info[$k] = $v;
        }
        $MemTotal = $info['MemTotal']; //总内存
        $MemFree = $info['MemFree']; //空闲内存
        $MeUsed = $MemTotal - $MemFree; //已使用内存

        $Buffers = $info['Buffers']; //buffer
        $Cached = $info['Cached']; //cache

        $MemRealUsed = $MemTotal - $MemFree - $Buffers - $Cached; //真实已使用内存
        $MemRealFree = $MemTotal - $MemRealUsed; //真实空闲内存

        $MemAvailable = $info['MemAvailable'];

        $result['total'] = round($MemTotal / 1024, 1);
        $result['free'] = round($MemRealFree / 1024, 1);
        $result['used'] = round($MemRealUsed / 1024, 1);
        $result['percent'] = round($MemRealUsed / $MemTotal * 100, 1);

        $data['status'] = 1;
        $data['message'] = '获取内存信息成功';
        $data['data'] = $result;
        return $data;
    }

    //获取系统平均负载,有问题未修复
    function GetLoadAvg($requestPayload) {
        //获取系统总核心数
        $array = file('/proc/cpuinfo');
        if (!$array)
            return false;
        foreach ($array as $key => $value) {
            if (strstr($value, "cpu cores")) {
                $result['cpu_cores'] = trim((explode(":", $value))[1]);
                break;
            }
        }
        //获取平均负载
        $array = file("/proc/loadavg");
        if (!$array) {
            return false;
        }
        $info = explode(" ", implode("", $array));

        //负载百分比 = 最近一分钟负载/CPU核心数*100%
        $result['minute_1_avg'] = '最近1分钟平均负载：' . round($info[0], 2);
        $result['minute_5_avg'] = '最近5分钟平均负载：' . round($info[1], 2);
        $result['minute_15_avg'] = '最近15分钟平均负载：' . round($info[2], 2);
        $result['avg_percent'] = round(($info[0] / $result['cpu_cores']) * 100, 2);
        $result['avg_percent'] = $result['avg_percent'] >= 100 ? 100 : $result['avg_percent'];

        $data['status'] = 1;
        $data['message'] = '获取系统平均负载信息成功';
        $data['data'] = $result;
        return $data;
    }

    //根据网卡名称获取实时网速 动态更新使用
    function GetNetworkByName($requestPayload) {
        $network_name = $requestPayload['network_name'];

        //获取时间 作为x坐标轴数据
        $time = date("H:i:s");
        $sleeptime = 1;
        //获取时间差值
        $info1 = $this->GetSingleNetwork($network_name);
        sleep($sleeptime);
        $info2 = $this->GetSingleNetwork($network_name);
        //计算
        $result = array();
        foreach ($info1 as $key => $value) {
            $result[$key]['name'] = $value['name'];
            $result[$key]['data'][0]['ReceiveSpeed'] = ($info2[$key]['Receive']['bytes'] - $info1[$key]['Receive']['bytes']) / $sleeptime / 1024; //1s内的网络速度 单位 kbps
            $result[$key]['data'][0]['TransmitSpeed'] = ($info2[$key]['Transmit']['bytes'] - $info1[$key]['Transmit']['bytes']) / $sleeptime / 1024; //1s内的网络速度 单位 kbps
            $result[$key]['data'][0]['time'] = $time;
        }

        $data['status'] = 1;
        $data['message'] = '获取特定网卡流量信息成功';
        $data['data'] = $result;
        return $data;
    }

    //获取网卡实时网速 第一次加载时使用
    function GetNetwork($requestPayload) {
        //获取时间 作为x坐标轴数据
        $time = date("H:i:s");
        $sleeptime = 1;
        //获取时间差值
        $info1 = $this->GetSingleNetwork("");
        sleep($sleeptime);
        $info2 = $this->GetSingleNetwork("");
        //计算
        $result = array();
        foreach ($info1 as $key => $value) {
//            $result[$key]['name'] = $value['name'];
            $result[$value['name']][0]['ReceiveSpeed'] = ($info2[$key]['Receive']['bytes'] - $info1[$key]['Receive']['bytes']) / $sleeptime / 1024; //1s内的网络速度 单位 kbps
            $result[$value['name']][0]['TransmitSpeed'] = ($info2[$key]['Transmit']['bytes'] - $info1[$key]['Transmit']['bytes']) / $sleeptime / 1024; //1s内的网络速度 单位 kbps
            $result[$value['name']][0]['time'] = $time;
        }

        $data['status'] = 1;
        $data['message'] = '获取网卡流量信息成功';
        $data['data'] = $result;
        return $data;
    }

    //获取单次网卡的流量
    private function GetSingleNetwork($network_name) {
        /*
         * bytes 接口发送或接收的数据的总字节数
         * packets 接口发送或接收的数据包总数
         * errs 由设备驱动程序检测到的发送或接收错误的总数
         * drop 设备驱动程序丢弃的数据包总数
         * fifo FIFO缓冲区错误的数量
         * frame 分组帧错误的数量
         * colls 接口上检测到的冲突数
         * compressed 设备驱动程序发送或接收的压缩数据包数
         * carrier 由设备驱动程序检测到的载波损耗的数量
         * multicast 设备驱动程序发送或接收的多播帧数
         */
        //$network_name为空表示获取除了本地回环外的所有网卡
        $networklist = array();
        $info = file("/proc/net/dev");
        //删除不是网卡的元素
        foreach ($info as $key => $value) {
            if (strstr($value, 'Receive') || strstr($value, 'bytes')) {
                unset($info[$key]);
                continue;
            }
            $info[$key] = trim($info[$key]);
        }
        //格式化
        $templist = array();
        foreach ($info as $key => $value) {
            $temp = explode(' ', $value);
            foreach ($temp as $key2 => $value2) {
                if ($value2 == '') {
                    unset($temp[$key2]);
                    continue;
                }
                $temp[$key2] = trim($temp[$key2]);
            }
            array_push($templist, array_values($temp));
        }
        $temp = array();
        //格式化
        foreach ($templist as $key => $value) {
            //去除网卡名称中的冒号
            $value[0] = str_replace(':', '', $value[0]);
            //删除本地回环口lo的数据
//            if ($value[0] == 'lo') {
//                continue;
//            }
            //只保留特定的网卡
            if ($network_name != '') {
                if ($value[0] != $network_name) {
                    continue;
                }
            }
            //网卡名称
            $temp['name'] = $value[0];
            //Receive 接收
            $temp['Receive']['bytes'] = $value[1]; //总接收
            //Transmit 发送
            $temp['Transmit']['bytes'] = $value[9]; //总发送
            //存入数组
            array_push($networklist, $temp);
        }

        return $networklist;
    }

}
