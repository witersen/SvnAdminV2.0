<?php

class Daemon {

    private $pidfile;

    function __construct() {
        $this->pidfile = dirname(__FILE__) . '/svnadmind.pid';
    }

    private function init_daemon() {
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

    private function init_socket() {
        //创建一个新的套接字上下文
        $context = new ZMQContext();
        //创建一个ZMQ响应套接字
        $reply = new ZMQSocket($context, ZMQ::SOCKET_REP);
        //绑定端口
        $reply->bind("tcp://127.0.0.1:6666");
        return $reply;
    }

    private function start_daemon() {
        if (file_exists($this->pidfile)) {
            echo "进程正在运行中 无需启动\n";
            exit(0);
        }
        return $this->init_daemon();
    }

    private function start() {
        $pid = $this->start_daemon();
        $reply = $this->init_socket();
        while (true) {
            $a = $reply->recv();
            shell_exec("echo 这是编码信息_$a >> /var/www/html/server.log");
            $b = urldecode($a);
            shell_exec("echo 这是解码信息_$b >> /var/www/html/server.log");
            $result = shell_exec($b);
            $reply->send($result);
        }
    }

    private function stop() {
        if (file_exists($this->pidfile)) {
            $pid = file_get_contents($this->pidfile);
            posix_kill($pid, 9);
            unlink($this->pidfile);
        }
    }

    public function run($argv) {
        if ($argv[1] == 'start') {
            $this->start();
        } else if ($argv[1] == 'stop') {
            $this->stop();
        } else {
            echo 'Usage: php svnadmind.php start|stop';
        }
    }

}

$deamon = new Daemon();
$deamon->run($argv);
