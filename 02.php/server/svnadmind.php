<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-09 22:30:20
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

require_once BASE_PATH . '/../app/util/Config.php';

class Daemon
{
    private $pidFile;
    private $workMode;
    private $scripts = [
        'start',
        'stop',
        'console'
    ];
    private $config_daemon;
    private $config_svn;

    function __construct()
    {
        $this->pidFile = dirname(__FILE__) . '/svnadmind.pid';

        Config::load(BASE_PATH . '/../config/');
        $this->config_daemon = Config::get('daemon');
        $this->config_svn = Config::get('svn');
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
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or exit('启动失败：socket_create 错误：' . socket_strerror(socket_last_error()) . PHP_EOL);

        //设置可重复使用端口号
        if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            exit('启动失败：设置套接字选项错误：' . socket_strerror(socket_last_error()) . PHP_EOL);
        }

        //绑定地址和端口
        socket_bind($socket, $this->config_daemon['IPC_ADDRESS'], $this->config_daemon['IPC_PORT']) or exit('启动失败：socket_bind 错误，可能是由于频繁启动，端口未释放，请稍后重试或检查端口冲突' . PHP_EOL);

        $rval = socket_get_option($socket, SOL_SOCKET, SO_REUSEADDR);

        if ($rval === false) {
            exit('启动失败：无法获取套接字选项：' . socket_strerror(socket_last_error()) . PHP_EOL);
        }

        //监听 设置并发队列的最大长度
        socket_listen($socket, $this->config_daemon['SOCKET_LISTEN_BACKLOG']);

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
        $cmmand = socket_read($client, $this->config_daemon['SOCKET_READ_LENGTH']);

        //console模式
        if ($this->workMode == 'console') {
            echo PHP_EOL . '---------receive---------' . PHP_EOL;
            echo $cmmand . PHP_EOL;
        }

        if (trim($cmmand) != '') {
            //定义错误输出文件路径
            $stderrFile = $this->config_svn['temp_base_path'] . uniqid();

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
        if (PHP_VERSION < '5.5') {
            exit('支持的最低PHP版本为 5.5 而不是 ' . PHP_VERSION . PHP_EOL);
        } else if (PHP_VERSION >= '8.0') {
            exit('支持的最高PHP版本低于 8.0 而不是 ' . PHP_VERSION . PHP_EOL);
        }
    }

    /**
     * 检查cli模式需要的函数是否被禁用
     */
    private function CheckDisabledFun()
    {
        $require_functions = ['shell_exec', 'passthru', 'pcntl_signal', 'pcntl_fork', 'pcntl_wait'];
        $disable_functions = explode(',', ini_get('disable_functions'));
        foreach ($disable_functions as $disable) {
            if (in_array(trim($disable), $require_functions)) {
                exit("启动失败：需要的 $disable 函数被禁用");
            }
        }
    }

    /**
     * 更新密钥
     */
    private function UpdateSign()
    {
        $signCon = sprintf("<?php\n\nreturn ['signature' => '%s'];", uniqid());
        file_put_contents(BASE_PATH . '/../config/sign.php', $signCon);
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
        $this->UpdateSign();
        echo '已在启动时自动更改系统的加密密钥，正在登录的用户会退出登录' . PHP_EOL;
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
                exit('无法进入调试模式，请先停止后台程序' . PHP_EOL);
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
                $this->CheckDisabledFun();
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
