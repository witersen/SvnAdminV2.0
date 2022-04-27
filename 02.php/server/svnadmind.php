<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-27 11:34:42
 * @Description: QQ:1801168257
 */

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
    private function initDaemon()
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
        $this->initSocket();
    }

    /**
     * 监听指定端口
     */
    private function initSocket()
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
                $this->handleRequest($client);
            } else {
            }
        }
    }

    /**
     * socket程序接收和处理请求
     */
    private function handleRequest($client)
    {
        //接收客户端发送的数据
        $data = socket_read($client, SOCKET_READ_LENGTH);

        //console
        $this->workMode == 'console' ? print_r(PHP_EOL . '---------receive---------' . PHP_EOL . $data . PHP_EOL) : '';

        if (trim($data) != '') {
            /**
             * shell_exec方法拿不到执行指令的错误抛出信息
             */
            // $result = shell_exec($data);

            /**
             * passthru会将所有的结果（包括正确的和错误的抛出信息输出）
             * 可以使用 ob_start ob_get_contents ob_end_clean 拿到缓冲区的内容
             * 
             * 此方法在console调试模式下会导致 $result 变量拿不到值；
             * 但是在daemon模式下 $result 变量可以拿到值（猜测是输入到终端与输出到缓冲区的影响）
             */
            ob_start();
            passthru($data);
            $result = ob_get_contents();
            ob_end_clean();
        } else {
            //探测程序会发送空信息
            $result = '';
        }

        //console
        $this->workMode == 'console' ? print_r(PHP_EOL . '---------result---------' . PHP_EOL . $result . PHP_EOL) : '';

        //处理没有返回内容的情况 否则 socket_write 遇到空内容会报错
        $result = $result == '' ? ISNULL : $result;

        //将结果返回给客户端
        socket_write($client, $result, strlen($result)) or die('启动失败：socket_write 错误' . PHP_EOL);

        //关闭会话
        socket_close($client);

        //退出进程
        exit();
    }

    /**
     * 检查操作系统是否符合要求
     */
    private function checkSysType()
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
    private function checkPhpVersion()
    {
        if (PHP_VERSION < Required_PHP_VERSION) {
            exit('启动失败：当前的PHP版本为：' . PHP_VERSION . '，要求的最低PHP版本为：' . Required_PHP_VERSION . PHP_EOL);
        }
    }

    /**
     * 检查需要的函数是否被禁用
     */
    private function checkDisabledFunction()
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
    private function startDaemon()
    {
        if (file_exists($this->pidFile)) {
            $pid = file_get_contents($this->pidFile);
            $result = trim(shell_exec("ps -ax | awk '{ print $1 }' | grep -e \"^$pid$\""));
            if (strstr($result, $pid)) {
                exit('程序正在运行中' . PHP_EOL);
            }
        }
        $this->initDaemon();
    }

    /**
     * 关闭守护进程
     */
    private function stopDaemon()
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
    private function startConsole()
    {
        $this->initSocket();
    }

    public function run($argv)
    {
        $this->checkSysType();
        $this->checkPhpVersion();
        $this->checkDisabledFunction();
        if (isset($argv[1])) {
            $this->workMode = $argv[1];
            if (!in_array($this->workMode, $this->scripts)) {
                exit('用法：php svnadmin.php [start | stop | console]' . PHP_EOL);
            }
            if ($this->workMode == 'start') {
                $this->startDaemon();
            } else if ($this->workMode == 'stop') {
                $this->stopDaemon();
            } else if ($this->workMode == 'console') {
                $this->startConsole();
            }
        } else {
            exit('用法：php svnadmin.php [start | stop | console]' . PHP_EOL);
        }
    }
}

if (preg_match('/cli/i', php_sapi_name())) {
    $deamon = new Daemon();
    $deamon->run($argv);
}
