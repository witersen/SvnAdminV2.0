<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

/**
 * 将工作模式限制在cli模式
 */
if (!preg_match('/cli/i', php_sapi_name())) {
    exit('require php-cli mode' . PHP_EOL);
}

/**
 * 开启错误信息 如需要调试 可取消注释
 */
// ini_set('display_errors', '1');
// error_reporting(E_ALL);

define('BASE_PATH', __DIR__ . '/..');

define('IPC_SVNADMIN', BASE_PATH . '/server/svnadmind.socket');

require_once BASE_PATH . '/app/util/Config.php';
require_once BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php';

use Medoo\Medoo;

class Daemon
{
    private $masterPidFile;
    private $taskPidFile;
    private $workMode;
    private $scripts = [
        'start',
        'stop',
        'console'
    ];
    private $configDaemon;
    private $configSvn;

    function __construct()
    {
        $this->masterPidFile = dirname(__FILE__) . '/svnadmind.pid';
        $this->taskPidFile = dirname(__FILE__) . '/task_svnadmind.pid';

        Config::load(BASE_PATH . '/config/');
        $this->configDaemon = Config::get('daemon');
        $this->configSvn = Config::get('svn');
    }

    /**
     * 创建TCP套接字并监听指定端口
     */
    private function InitSocket()
    {
        if (file_exists(IPC_SVNADMIN)) {
            unlink(IPC_SVNADMIN);
        }

        //创建套接字
        $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0) or die(sprintf('创建套接字失败[%s]%s', socket_strerror(socket_last_error()), PHP_EOL));

        //绑定地址和端口
        @socket_bind($socket, IPC_SVNADMIN, 0) or die(sprintf('绑定失败[%s][%s]%s', socket_strerror(socket_last_error()), IPC_SVNADMIN, PHP_EOL));

        //监听 设置并发队列的最大长度
        @socket_listen($socket, $this->configDaemon['socket_listen_backlog']) or die(sprintf('监听失败[%s]%s', socket_strerror(socket_last_error()), PHP_EOL));

        //使其它用户可用
        shell_exec('chmod 777 ' . IPC_SVNADMIN);

        // 创建任务进程 用于处理任务
        $pid = pcntl_fork();
        if ($pid == -1) {
            die(sprintf('pcntl_fork失败[%s]%s', socket_strerror(socket_last_error()), PHP_EOL));
        } elseif ($pid == 0) {
            file_put_contents($this->taskPidFile, getmypid());

            $this->ClearTask();
            while (true) {
                $this->HandleTask();
                sleep(3);
            }
            exit();
        }

        while (true) {
            //非阻塞式回收僵尸进程
            pcntl_wait($status, WNOHANG);

            $client = @socket_accept($socket) or die(sprintf('接收连接失败[%s]%s', socket_strerror(socket_last_error()), PHP_EOL));

            //非阻塞式回收僵尸进程
            pcntl_wait($status, WNOHANG);

            $pid = pcntl_fork();
            if ($pid == -1) {
                die(sprintf('pcntl_fork失败[%s]%s', socket_strerror(socket_last_error()), PHP_EOL));
            } elseif ($pid == 0) {
                $this->HandleRequest($client);
            } else {
            }
        }
    }

    /**
     * 清理垃圾任务
     *
     * @return void
     */
    private function ClearTask()
    {
        $task_error_log_file = $this->configSvn['log_base_path'] . 'task_error.log';

        $configDatabase = Config::get('database');
        $configSvn = Config::get('svn');
        if (array_key_exists('database_file', $configDatabase)) {
            $configDatabase['database_file'] = sprintf($configDatabase['database_file'], $configSvn['home_path']);
        }
        try {
            $database = new Medoo($configDatabase);
        } catch (\Exception $e) {
            $message = sprintf('[%s][任务清理守护进程异常][%s]%s', date('Y-m-d H:i:s'), $e->getMessage(), PHP_EOL);
            file_put_contents($task_error_log_file, $message, FILE_APPEND);
            return;
        }

        $tasks = $database->select('tasks', [
            'task_id [Int]',
            'task_name',
            'task_status [Int]',
            'task_cmd',
            'task_unique',
            'task_log_file',
            'task_optional',
            'task_create_time',
            'task_update_time'
        ], [
            'OR' => [
                'task_status' => [
                    2
                ]
            ],
            'ORDER' => [
                'task_id'  => 'ASC'
            ]
        ]);

        foreach ($tasks as $task) {
            $pid = trim(shell_exec(sprintf("ps aux | grep -v grep | grep %s | awk 'NR==1' | awk '{print $2}'", $task['task_unique'])));

            if (empty($pid)) {
                $database->update('tasks', [
                    'task_status' => 5
                ], [
                    'task_id' => $task['task_id']
                ]);

                continue;
            }

            clearstatcache();
            if (!is_dir("/proc/$pid")) {
                $database->update('tasks', [
                    'task_status' => 4
                ], [
                    'task_id' => $task['task_id']
                ]);

                continue;
            }

            shell_exec(sprintf("kill -15 %s && kill -9 %s", $pid, $pid));

            $database->update('tasks', [
                'task_status' => 5
            ], [
                'task_id' => $task['task_id']
            ]);
        }
    }

    /**
     * 处理任务
     */
    private function HandleTask()
    {
        $task_error_log_file = $this->configSvn['log_base_path'] . 'task_error.log';

        $configDatabase = Config::get('database');
        $configSvn = Config::get('svn');
        if (array_key_exists('database_file', $configDatabase)) {
            $configDatabase['database_file'] = sprintf($configDatabase['database_file'], $configSvn['home_path']);
        }
        try {
            $database = new Medoo($configDatabase);
        } catch (\Exception $e) {
            $message = sprintf('[%s][任务处理守护进程异常][120s后重试][%s]', date('Y-m-d H:i:s'), $e->getMessage(), PHP_EOL);
            file_put_contents($task_error_log_file, $message, FILE_APPEND);
            sleep(120);
            return;
        }

        $tasks = $database->select('tasks', [
            'task_id',
            'task_name',
            'task_status',
            'task_cmd',
            'task_type',
            'task_unique',
            'task_log_file',
            'task_optional',
            'task_create_time',
            'task_update_time'
        ], [
            'task_status' => 1
        ]);

        foreach ($tasks as $task) {
            //开始执行
            $database->update('tasks', [
                'task_status' => 2
            ], [
                'task_id' => $task['task_id']
            ]);

            file_put_contents($task['task_log_file'], sprintf('%s%s', $task['task_name'], PHP_EOL), FILE_APPEND);
            file_put_contents($task['task_log_file'], sprintf('%s------------------任务执行中------------------%s', PHP_EOL, PHP_EOL), FILE_APPEND);

            ob_start();
            if ($task['task_type'] == 'svnadmin:load') {
                passthru(sprintf("%s &>> '%s'", $task['task_cmd'], $task['task_log_file']));
            } else {
                passthru(sprintf("%s", $task['task_cmd'], $task['task_log_file']));
            }
            $buffer = ob_get_contents();
            ob_end_clean();
            file_put_contents($task_error_log_file, $buffer, FILE_APPEND);
            file_put_contents($task['task_log_file'], $buffer, FILE_APPEND);

            //执行结束
            $database->update('tasks', [
                'task_status' => 3,
                'task_update_time' => date('Y-m-d H:i:s')
            ], [
                'task_id' => $task['task_id']
            ]);

            file_put_contents($task['task_log_file'], sprintf('%s------------------任务结束------------------%s', PHP_EOL, PHP_EOL), FILE_APPEND);
        }
    }

    /**
     * 接收TCP连接并处理指令
     */
    private function HandleRequest($client)
    {
        $length = $this->configDaemon['socket_data_length'];

        //接收客户端发送的数据
        if (empty($receive = socket_read($client, $length))) {
            exit();
        }

        $receive = json_decode($receive, true);

        if (!isset($receive['type']) || !isset($receive['content'])) {
            exit();
        }

        $type = $receive['type'];
        $content = $receive['content'];

        //console模式
        if ($this->workMode == 'console') {
            echo PHP_EOL . '---------receive---------' . PHP_EOL;
            print_r($receive);
        }

        if ($type == 'passthru') {
            //定义错误输出文件路径
            $stderrFile = tempnam(sys_get_temp_dir(), 'svnadmin_');

            //将标准错误重定向到文件
            //使用状态码来标识错误信息
            ob_start();
            passthru($content . " 2>$stderrFile", $code);
            $buffer = ob_get_contents();
            ob_end_clean();

            //将错误信息和正确信息分类收集
            $result = [
                'code' => $code,
                'result' => trim($buffer),
                'error' => file_get_contents($stderrFile)
            ];

            @unlink($stderrFile);
        } else {
            $result = [
                'code' => 0,
                'result' => '',
                'error' => ''
            ];
        }

        //console模式
        if ($this->workMode == 'console') {
            echo PHP_EOL . '---------result---------' . PHP_EOL;
            echo 'code: ' . $result['code'] . PHP_EOL;
            echo 'result: ' . $result['result'] . PHP_EOL;
            echo 'error: ' . $result['error'] . PHP_EOL;
        }

        //返回json格式
        @socket_write($client, json_encode($result), $length) or die(sprintf('socket_write失败[%s]%s', socket_strerror(socket_last_error()), PHP_EOL));

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
            die(sprintf('当前操作系统不为Linux%s', PHP_EOL));
        }
        if (file_exists('/etc/redhat-release')) {
            $readhat_release = file_get_contents('/etc/redhat-release');
            $readhat_release = strtolower($readhat_release);
            if (strstr($readhat_release, 'centos')) {
                if (strstr($readhat_release, '8.')) {
                    // return 'centos 8';
                } elseif (strstr($readhat_release, '7.')) {
                    // return 'centos 7';
                } else {
                    echo '===============================================' . PHP_EOL;
                    echo '警告！当前操作系统版本未测试，使用过程中可能会遇到问题！' . PHP_EOL;
                    echo '===============================================' . PHP_EOL;
                }
            } elseif (strstr($readhat_release, 'rocky')) {
                // return 'rocky';
            } else {
                echo '===============================================' . PHP_EOL;
                echo '警告！当前操作系统版本未测试，使用过程中可能会遇到问题！' . PHP_EOL;
                echo '===============================================' . PHP_EOL;
            }
        } elseif (file_exists('/etc/lsb-release')) {
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
        $version = Config::get('version');
        if (isset($version['php']['lowest']) && !empty($version['php']['lowest'])) {
            if (PHP_VERSION < $version['php']['lowest']) {
                die(sprintf('支持的最低PHP版本为[%s]当前的PHP版本为[%s]%s', $version['php']['lowest'], PHP_VERSION, PHP_EOL));
            }
        }
        if (isset($version['php']['highest']) && !empty($version['php']['highest'])) {
            if (PHP_VERSION >= $version['php']['highest']) {
                die(sprintf('支持的最高PHP版本为[%s]当前的PHP版本为[%s]%s', $version['php']['highest'], PHP_VERSION, PHP_EOL));
            }
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
        Config::load(BASE_PATH . '/config/');
        $sign = Config::get('sign');

        $content = "<?php\n\nreturn [\n";
        foreach ($sign as $key => $value) {
            $content .= sprintf("'%s' => '%s',%s", $key, $key == 'signature' ? uniqid() : $value, "\n");
        }
        $content .= "];\n";

        file_put_contents(BASE_PATH . '/config/sign.php', $content);
    }

    /**
     * 停止
     */
    private function Stop()
    {
        clearstatcache();

        if (file_exists($this->masterPidFile)) {
            $pid = file_get_contents($this->masterPidFile);
            posix_kill((int)$pid, 9);
            @unlink($this->masterPidFile);
        }

        if (file_exists($this->taskPidFile)) {
            $pid = file_get_contents($this->taskPidFile);
            posix_kill((int)$pid, 9);
            @unlink($this->taskPidFile);
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
        file_put_contents($this->masterPidFile, getmypid());
        $this->UpdateSign();

        echo PHP_EOL;
        echo '----------------------------------------' . PHP_EOL;
        echo '守护进程(svnadmind)启动成功' . PHP_EOL;
        echo '已自动更改系统加密密钥，在线用户会退出登录' . PHP_EOL;
        echo '----------------------------------------' . PHP_EOL;
        echo PHP_EOL;

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
        if (file_exists($this->masterPidFile)) {
            $pid = file_get_contents($this->masterPidFile);
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
                } elseif ($this->workMode == 'start') {
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
