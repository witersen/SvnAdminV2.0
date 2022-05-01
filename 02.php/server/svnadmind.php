<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-01 21:03:26
 * @Description: QQ:1801168257
 */

/**
 * 将工作模式限制在cli模式
 */
if (!preg_match('/cli/i', php_sapi_name())) {
    exit('require php-cli mode');
}

ini_set('display_errors', '1');

error_reporting(E_ALL);

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/../config/subversion.config.php';
require_once BASE_PATH . '/../config/daemon.config.php';

class Daemon
{
    private $pidFile;
    private $workMode;
    private $scripts = [
        'start',
        'stop',
        'console'
    ];

    function __construct()
    {
        $this->pidFile = dirname(__FILE__) . '/svnadmind.pid';
    }

    /**
     * 将程序变为守护进程
     */
    private function InitDeamon()
    {
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit('pcntl_fork 错误');
        } elseif ($pid > 0) {
            exit();
        }
        $sid = posix_setsid();
        if (!$sid) {
            exit('posix_setsid 错误');
        }
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit('pcntl_fork 错误');
        } elseif ($pid > 0) {
            exit();
        }
        chdir('/');
        umask(0);
        if (defined('STDIN')) {
            fclose(STDIN);
        }
        if (defined('STDOUT')) {
            fclose(STDOUT);
        }
        if (defined('STDERR')) {
            fclose(STDERR);
        }
        file_put_contents($this->pidFile, getmypid());
        $this->InitSocket();
    }

    /**
     * 创建TCP套接字并监听指定端口
     */
    private function InitSocket()
    {
        //创建套接字
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or exit('启动失败：socket_create 错误' . PHP_EOL);

        //绑定地址和端口
        socket_bind($socket, IPC_ADDRESS, IPC_PORT) or exit('启动失败：socket_bind 错误，可能是由于频繁启动，端口未释放，请稍后重试或检查端口冲突' . PHP_EOL);

        //设置可重复使用端口号
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

        //监听 设置并发队列的最大长度
        socket_listen($socket, SOCKET_LISTEN_BACKLOG);

        while (true) {
            //非阻塞式回收僵尸进程
            pcntl_wait($status, WNOHANG);

            $client = socket_accept($socket) or exit('启动失败：socket_accept 错误' . PHP_EOL);

            //非阻塞式回收僵尸进程
            pcntl_wait($status, WNOHANG);

            $pid = pcntl_fork();
            if ($pid == -1) {
                exit('启动失败：pcntl_fork 错误' . PHP_EOL);
            } else if ($pid == 0) {
                $this->HandleRequest($client);
            } else {
            }
        }
    }

    /**
     * 接收TCP连接并处理指令
     */
    private function HandleRequest($client)
    {
        //接收客户端发送的数据
        $cmmand = socket_read($client, SOCKET_READ_LENGTH);

        //console模式
        if ($this->workMode == 'console') {
            echo PHP_EOL . '---------receive---------' . PHP_EOL;
            echo $cmmand . PHP_EOL;
        }

        if (trim($cmmand) != '') {
            //定义错误输出文件路径
            $stderrFile = TEMP_PATH . uniqid();

            //将标准错误重定向到文件
            //使用状态码来标识错误信息
            ob_start();
            passthru($cmmand . " 2>$stderrFile", $resultCode);
            $buffer = ob_get_contents();
            ob_end_clean();

            //将错误信息和正确信息分类收集
            $result = [
                'resultCode' => $resultCode,
                'result' => trim($buffer),
                'error' => file_get_contents($stderrFile)
            ];

            //销毁文件
            unlink($stderrFile);
        } else {
            //探测程序会发送空信息
            $result = [
                'resultCode' => 0,
                'result' => '',
                'error' => ''
            ];
        }

        //console模式
        if ($this->workMode == 'console') {
            echo PHP_EOL . '---------result---------' . PHP_EOL;
            echo 'resultCode: ' . $result['resultCode'] . PHP_EOL;
            echo 'result: ' . $result['result'] . PHP_EOL;
            echo 'error: ' . $result['error'] . PHP_EOL;
        }

        //将结果序列化并返回
        socket_write($client, serialize($result), strlen(serialize($result))) or die('失败：socket_write 错误' . PHP_EOL);

        //关闭会话
        socket_close($client);

        //退出进程
        exit();
    }

    /**
     * 检查操作系统是否符合要求
     */
    private function CheckSysType()
    {
        if (PHP_OS != 'Linux') {
            exit('启动失败：当前操作系统不为Linux' . PHP_EOL);
        }
        if (file_exists('/etc/redhat-release')) {
            $info = file_get_contents('/etc/redhat-release');
            if (!strstr($info, 'CentOS') && (strstr($info, '8.') || strstr($info, '7.'))) {
                exit('启动失败：当前仅支持 CentOS 7和 CentOS8 操作系统' . PHP_EOL);
            }
            return;
        }
        exit('启动失败：当前仅支持 CentOS 7和 CentOS8 操作系统' . PHP_EOL);
    }

    /**
     * 检查php版本是否符合要求
     */
    private function CheckPhpVersion()
    {
        if (PHP_VERSION < Required_PHP_VERSION) {
            exit('启动失败：当前的PHP版本为：' . PHP_VERSION . '，要求的最低PHP版本为：' . Required_PHP_VERSION . PHP_EOL);
        }
    }

    /**
     * 检查需要的函数是否被禁用
     */
    private function CheckDisabledFunction()
    {
        $disabled_function = explode(',', ini_get('disable_functions'));
        $cli_needed_function = unserialize(CLI_NEEDED_FUNCTION);
        foreach ($cli_needed_function as $key => $value) {
            if (!in_array($value, $disabled_function)) {
                unset($cli_needed_function[$key]);
            }
        }
        if (!empty($cli_needed_function)) {
            exit('启动失败：需要的以下函数被禁用：' . PHP_EOL . implode(' ', $cli_needed_function) . PHP_EOL);
        }
    }

    /**
     * 以守护进程模式工作
     */
    private function StartDaemon()
    {
        if (file_exists($this->pidFile)) {
            $pid = file_get_contents($this->pidFile);
            $result = trim(shell_exec("ps -ax | awk '{ print $1 }' | grep -e \"^$pid$\""));
            if (strstr($result, $pid)) {
                exit('程序正在运行中' . PHP_EOL);
            }
        }
        $this->InitDeamon();
    }

    /**
     * 关闭守护进程
     */
    private function StopDaemon()
    {
        if (file_exists($this->pidFile)) {
            $pid = file_get_contents($this->pidFile);
            posix_kill((int)$pid, 9);
            unlink($this->pidFile);
        }
    }

    /**
     * 以控制台模式工作 用于调试
     */
    private function StartConsole()
    {
        if (file_exists($this->pidFile)) {
            $pid = file_get_contents($this->pidFile);
            $result = trim(shell_exec("ps -ax | awk '{ print $1 }' | grep -e \"^$pid$\""));
            if (strstr($result, $pid)) {
                exit('程序正在运行中，请先停止' . PHP_EOL);
            }
        }
        $this->InitSocket();
    }

    public function Run($argv)
    {

        if (isset($argv[1])) {
            $this->workMode = $argv[1];
            if (!in_array($this->workMode, $this->scripts)) {
                exit('用法：php svnadmin.php [start | stop | console]' . PHP_EOL);
            }
            if ($this->workMode == 'stop') {
                $this->StopDaemon();
            } else {
                $this->CheckSysType();
                $this->CheckPhpVersion();
                $this->CheckDisabledFunction();
                if ($this->workMode == 'start') {
                    $this->StartDaemon();
                } else if ($this->workMode == 'console') {
                    $this->StartConsole();
                }
            }
        } else {
            exit('用法：php svnadmin.php [start | stop | console]' . PHP_EOL);
        }
    }
}

$deamon = new Daemon();
$deamon->Run($argv);
