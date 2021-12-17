<?php

ini_set('display_errors', 1);

error_reporting(E_ALL);

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/../config/manual.config.php';

class Daemon
{

    private $pidfile;
    private $state;
    private $cmdlist = array(
        "start",
        "stop",
        "console"
    );

    function __construct()
    {
        $this->pidfile = dirname(__FILE__) . '/svnadmind.pid';
    }

    private function init_daemon()
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
        chdir("/");
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
        file_put_contents($this->pidfile, getmypid());
        return getmypid();
    }

    private function init_socket()
    {
        //创建套接字
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or exit("socket_create 错误\n");

        //绑定地址和端口
        socket_bind($socket, IPC_ADDRESS, (int)IPC_PORT) or exit("socket_bind 错误 可能是由于频繁启动 端口未释放 请稍后重试或检查端口冲突\n");

        //设置可重复使用端口号
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

        //监听 设置并发队列的最大长度
        socket_listen($socket, (int)SOCKET_LISTEN_BACKLOG);

        while (true) {
            //非阻塞式回收僵尸进程
            pcntl_wait($status, WNOHANG);

            $clien = socket_accept($socket) or exit("socket_accept 错误\n");

            //非阻塞式回收僵尸进程
            pcntl_wait($status, WNOHANG);

            $pid = pcntl_fork();
            if ($pid == -1) {
                exit("pcntl_fork 错误\n");
            } else if ($pid == 0) {
                $this->handle_request($clien);
            } else {
            }
        }
    }

    private function check_sys_type()
    {
        if (PHP_OS != 'Linux') {
            exit("启动失败 \n当前操作系统不为Linux\n");
        }
        if (file_exists('/etc/redhat-release')) {
            $info = file_get_contents('/etc/redhat-release');
            if (!strstr($info, 'CentOS') && (strstr($info, '8.') || strstr($info, '7.'))) {
                exit("启动失败 \n仅支持CentOS 7 和 CentOS8 系统\n");
            }
            return;
        }
        exit("启动失败 \n不支持当前操作系统\n");
    }

    private function check_php_version()
    {
        if (PHP_VERSION < Required_PHP_VERSION) {
            echo "启动失败 \n当前的PHP版本为 " . PHP_VERSION . " 最低的PHP版本要求为 " . Required_PHP_VERSION . "\n";
            exit();
        }
    }

    private function check_disabled_function()
    {
        $disabled_function = explode(',', ini_get('disable_functions'));
        $needed_function = NEEDED_FUNCTION;
        foreach ($needed_function as $key => $value) {
            if (!in_array($value, $disabled_function)) {
                unset($needed_function[$key]);
            }
        }
        if (!empty($needed_function)) {
            echo "启动失败 \n需要的以下PHP函数被禁用:\n" . implode("\n", $needed_function) . "\n";
            exit();
        }
    }

    private function handle_request($clien)
    {
        //接收客户端发送的数据
        $data = socket_read($clien, (int)SOCKET_READ_LENGTH);

        //console
        $this->state == "console" ? print_r("\n---------接收内容---------\n" . $data . "\n") : "";

        if (trim($data) != "") {
            //执行
            $result = shell_exec($data);
        } else {
            //探测程序会发送空信息
            $result = "";
        }

        //console
        $this->state == "console" ? print_r("\n---------执行结果---------\n" . $result . "\n") : "";

        //处理没有返回内容的情况 否则 socket_write 遇到空内容会报错
        $result = $result == "" ? ISNULL : $result;

        //将结果返回给客户端
        socket_write($clien, $result, strlen($result)) or die("socket_write 错误");

        //关闭会话
        socket_close($clien);

        //退出进程
        exit();
    }

    private function start_daemon()
    {
        if (file_exists($this->pidfile)) {
            $pid = file_get_contents($this->pidfile);
            $result = trim(shell_exec("ps -ax | awk '{ print $1 }' | grep -e \"^$pid$\""));
            if (strstr($result, $pid)) {
                echo "进程正在运行中 无需启动\n";
                exit();
            }
        }
        return $this->init_daemon();
    }

    private function start()
    {
        $this->start_daemon();
        $this->init_socket();
    }

    private function stop()
    {
        if (file_exists($this->pidfile)) {
            $pid = file_get_contents($this->pidfile);
            posix_kill($pid, 9);
            unlink($this->pidfile);
        }
    }

    public function run($argv)
    {
        $this->check_sys_type();
        $this->check_php_version();
        $this->check_disabled_function();
        if (isset($argv[1])) {
            $this->state = $argv[1];
            if (!in_array($this->state, $this->cmdlist)) {
                echo "用法: php svnadmind.php [start] [stop] [console]\n";
                exit();
            }
            if ($this->state == 'start') {
                $this->start();
            } else if ($this->state == 'stop') {
                $this->stop();
            } else if ($this->state == 'console') {
                $this->init_socket();
            }
        } else {
            echo "用法: php svnadmind.php [start] [stop] [console]\n";
        }
    }
}

$deamon = new Daemon();
$deamon->run($argv);
