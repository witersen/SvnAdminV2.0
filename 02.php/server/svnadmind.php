<?php

ini_set('display_errors', 1);

error_reporting(E_ALL);

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/../config/config.php';

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
            exit("pcntl_fork 错误");
        } elseif ($pid > 0) {
            exit();
        }
        $sid = posix_setsid();
        if (!$sid) {
            exit("posix_setsid 错误");
        }
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit("pcntl_fork 错误");
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
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create 错误");

        //绑定地址和端口
        socket_bind($socket, IPC_ADDRESS, IPC_PORT) or die("socket_bind 错误");

        //设置可重复使用端口号
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

        //监听 设置并发队列的最大长度
        socket_listen($socket, SOCKET_LISTEN_BACKLOG);

        while (true) {
            //非阻塞式回收僵尸进程
            pcntl_wait($status, WNOHANG);

            $clien = socket_accept($socket) or die("socket_accept 错误");

            //非阻塞式回收僵尸进程
            pcntl_wait($status, WNOHANG);

            $pid = pcntl_fork();
            if ($pid == -1) {
                die('pcntl_fork 错误');
            } else if ($pid == 0) {
                $this->handle_request($clien);
            } else {
            }
        }
    }

    private function handle_request($clien)
    {
        //接收客户端发送的数据
        $data = socket_read($clien, SOCKET_READ_LENGTH);

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
        clearstatcache(true, DB_FILE);
        if (!file_exists(DB_FILE)) {
            echo "数据库文件不存在 请复制数据库文件到指定目录\n";
            return;
        }
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
