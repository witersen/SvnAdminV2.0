<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:06
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-20 16:06:09
 * @Description: QQ:1801168257
 */

/**
 * 将工作模式限制在cli模式
 */
if (!preg_match('/cli/i', php_sapi_name())) {
    exit('require php-cli mode' . PHP_EOL);
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
     * 创建TCP套接字并监听指定端口
     */
    private function InitSocket()
    {
        if (file_exists($this->config_daemon['ipc_file'])) {
            unlink($this->config_daemon['ipc_file']);
        }

        //创建套接字
        $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0) or die('创建套接字失败：' . socket_strerror(socket_last_error()) . PHP_EOL);

        //绑定地址和端口
        @socket_bind($socket, $this->config_daemon['ipc_file'], 0) or die('绑定失败：' . socket_strerror(socket_last_error()) . PHP_EOL);

        //监听 设置并发队列的最大长度
        @socket_listen($socket, $this->config_daemon['socket_listen_backlog']) or die('监听失败：' . socket_strerror(socket_last_error()) . PHP_EOL);

        //使其它用户可用
        shell_exec('chmod 777 ' . $this->config_daemon['ipc_file']);

        while (true) {
            //非阻塞式回收僵尸进程
            pcntl_wait($status, WNOHANG);

            $client = @socket_accept($socket) or die('接收连接失败：' . socket_strerror(socket_last_error()) . PHP_EOL);

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
        $length = $this->config_daemon['socket_data_length'];

        //接收客户端发送的数据
        $receive = socket_read($client, $length);

        $type = 'detect';
        $content = '';

        if (!empty($receive)) {
            $receive = json_decode($receive, true);

            $type = $receive['type'];
            $content = $receive['content'];
        }

        //console模式
        if ($this->workMode == 'console') {
            echo PHP_EOL . '---------receive---------' . PHP_EOL;
            print_r($receive);
        }

        if ($type == 'passthru') {
            if (trim($content) != '') {
                //定义错误输出文件路径
                $stderrFile = $this->config_svn['temp_base_path'] . uniqid();

                //将标准错误重定向到文件
                //使用状态码来标识错误信息
                ob_start();
                passthru($content . " 2>$stderrFile", $resultCode);
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
        } else {
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
        @socket_write($client, json_encode($result), $length) or die('socket_write失败：' . socket_strerror(socket_last_error()) . PHP_EOL);

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
            $readhat_release = file_get_contents('/etc/redhat-release');
            $readhat_release = strtolower($readhat_release);
            if (strstr($readhat_release, 'centos')) {
                if (strstr($readhat_release, '8.')) {
                    // return 'centos 8';
                } else if (strstr($readhat_release, '7.')) {
                    // return 'centos 7';
                } else {
                    echo '===============================================' . PHP_EOL;
                    echo '警告！当前操作系统版本未测试，使用过程中可能会遇到问题！' . PHP_EOL;
                    echo '===============================================' . PHP_EOL;
                }
            } else if (strstr($readhat_release, 'rocky')) {
                // return 'rocky';
            } else {
                echo '===============================================' . PHP_EOL;
                echo '警告！当前操作系统版本未测试，使用过程中可能会遇到问题！' . PHP_EOL;
                echo '===============================================' . PHP_EOL;
            }
        } else if (file_exists('/etc/lsb-release')) {
            // return 'ubuntu';
        } else {
            echo '===============================================' . PHP_EOL;
            echo '警告！当前操作系统版本未测试，使用过程中可能会遇到问题！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
        }
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
                exit("启动失败：需要的 $disable 函数被禁用" . PHP_EOL);
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
     * 停止
     */
    private function Stop()
    {
        if (file_exists($this->pidFile)) {
            $pid = file_get_contents($this->pidFile);
            posix_kill((int)$pid, 9);
            unlink($this->pidFile);
        }

        //如果为 Linux 平台，且安装了 ps 程序，检测是否正确关闭了相关程序
        if (PHP_OS == 'Linux') {
            $result = shell_exec('which ps 2>/dev/null');
            if (!empty($result)) {
                $result2 = shell_exec("ps auxf | grep -v 'grep' | grep -v " . getmypid() . " | grep svnadmind.php");
                if (!empty($result2)) {
                    echo '请确保您成功关闭了该守护进程程序！' . PHP_EOL;
                    echo '因为检测到以下疑似进程正在运行:' . PHP_EOL;
                    echo $result2;
                }
            }
        }
    }

    /**
     * 启动
     */
    private function Start()
    {
        file_put_contents($this->pidFile, getmypid());
        $this->UpdateSign();
        echo '启动成功' . PHP_EOL;
        echo '可进行网站访问' . PHP_EOL;
        echo '检出SVN仓库前请注意放行协议端口（默认3690）' . PHP_EOL;
        echo '已自动更改系统加密密钥，在线用户会退出登录' . PHP_EOL;
        echo '建议将本程序通过nohup启动或加入系统管理' . PHP_EOL;

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
        $this->InitSocket();
    }

    /**
     * 调试
     */
    private function Console()
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
                exit('用法：php svnadmin.php [' . implode(' | ', $this->scripts) . ']' . PHP_EOL);
            }
            if ($this->workMode == 'stop') {
                $this->Stop();
            } else {
                $this->CheckSysType();
                $this->CheckPhpVersion();
                $this->CheckDisabledFun();
                if ($this->workMode == 'console') {
                    $this->Console();
                } else if ($this->workMode == 'start') {
                    $this->Start();
                }
            }
        } else {
            exit('用法：php svnadmin.php [' . implode(' | ', $this->scripts) . ']' . PHP_EOL);
        }
    }
}

$deamon = new Daemon();
$deamon->Run($argv);
