<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

class Daemon
{

    private $pidfile;

    function __construct()
    {
        $this->pidfile = dirname(__FILE__) . '/svnadmind.pid';
    }

    private function init_daemon()
    {
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit("pcntl_fork error");
        } elseif ($pid > 0) {
            exit(0);
        }
        $sid = posix_setsid();
        if (!$sid) {
            exit("错误");
        }
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit("pcntl_fork error");
        } elseif ($pid > 0) {
            exit(0);
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
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create error");

        //绑定地址和端口
        socket_bind($socket, "127.0.0.1", "6666") or die("socket_bind error");

        //设置可重复使用端口号
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

        //监听 设置并发队列的最大长度
        socket_listen($socket, 5000);
        while (true) {
            $clien = socket_accept($socket) or die("socket_accept error");

            //如果父进程不关心子进程什么时候结束,子进程结束后，内核会回
            //避免了正常情况下僵尸进程的产生
            pcntl_signal(SIGCHLD, SIG_IGN);

            $pid = pcntl_fork();
            if ($pid == -1) {
                die('fork error');
            } else if ($pid == 0) {
                $this->handle_request($clien);
            } else {
            }
        }
    }

    private function handle_request($clien)
    {
        //接收客户端发送的数据
        $data = socket_read($clien, 8192);

        //执行
        $result = shell_exec($data);

        //处理没有返回内容的情况 否则 socket_write 遇到空内容会报错
        $result = $result == "" ? "-NULL-" : $result;

        //将结果返回给客户端
        socket_write($clien, $result, strlen($result)) or die("socket_write error");

        //关闭会话
        socket_close($clien);

        //退出进程
        exit();
    }

    private function start_daemon()
    {
        if (file_exists($this->pidfile)) {
            echo "进程正在运行中 无需启动\n";
            exit(0);
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
        if (isset($argv[1])) {
            if ($argv[1] == 'start') {
                $this->start();
            } else if ($argv[1] == 'stop') {
                $this->stop();
            }
        } else {
            echo "Usage: php svnadmind.php start|stop\n";
        }
    }
}

$deamon = new Daemon();
$deamon->run($argv);
