<?php

if (!preg_match('/cli/i', php_sapi_name())) {
    exit('require php-cli mode');
}

//与web入口文件保持一致
define('BASE_PATH', __DIR__ . '/..');

date_default_timezone_set('PRC');

auto_require(BASE_PATH . '/config/');

auto_require(BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php');

auto_require(BASE_PATH . '/app/service/base/Base.php');
auto_require(BASE_PATH . '/app/service/');

function auto_require($path, $recursively = false)
{
    if (is_file($path)) {
        if (substr($path, -4) == '.php') {
            require_once $path;
        }
    } else {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . '/' . $file)) {
                    $recursively ? auto_require($path . '/' . $file, true) : '';
                } else {
                    if (substr($file, -4) == '.php') {
                        require_once $path . '/' . $file;
                    }
                }
            }
        }
    }
}

use app\service\Mail;
use Medoo\Medoo;

use app\service\Svnuser as ServiceSvnuser;
use app\service\Svngroup as ServiceSvngroup;
use app\service\Svnrep as ServiceSvnrep;


class Command
{
    private $database;

    private $configDb;

    private $configSvn;

    private $configBin;

    private $argc;

    private $argv;

    private $crond;

    private $code = 0;

    private $currentRep = '';

    private $taskType = [
        '1' => '仓库备份[dump-全量]',
        '2' => '仓库备份[dump-增量-deltas]',
        '3' => '仓库备份[hotcopy-全量]',
        '4' => '仓库备份[hotcopy-增量]',
        '5' => '仓库检查',
        '6' => 'shell脚本',
        '7' => '同步SVN用户',
        '8' => '同步SVN分组',
        '9' => '同步SVN仓库'
    ];

    function __construct($argc, $argv)
    {
        //接收参数 校验参数
        if (!isset($argv[1]) || !isset($argv[2])) {
            print_r(sprintf('参数不完整-自动退出%s', PHP_EOL));
            exit;
        }

        $this->argc = $argc;
        $this->argv = $argv;

        Config::load(BASE_PATH . '/config/');

        //配置
        $this->configDb = Config::get('database');
        $this->configSvn = Config::get('svn');
        $this->configBin =  Config::get('bin');

        //数据库连接
        if (array_key_exists('database_file', $this->configDb)) {
            $this->configDb['database_file'] = sprintf($this->configDb['database_file'], $this->configSvn['home_path']);
        }
        try {
            $this->database = new Medoo($this->configDb);
        } catch (\Exception $e) {
            print_r(sprintf('数据库连接失败[%s]', $e->getMessage()));
            exit;
        }


        //查询参数详情
        $this->crond = $this->database->get('crond', '*', [
            'sign' => $this->argv[2]
        ]);

        if (empty($this->crond)) {
            print_r(sprintf('数据库中没有与标识[%s]相匹配的任务计划-自动退出%s', $this->argv[2], PHP_EOL));
            exit;
        }

        //更新执行时间
        $this->database->update('crond', [
            'last_exec_time' => date('Y-m-d H:i:s'),
        ], [
            'sign' => $this->argv[2]
        ]);
    }

    /**
     * 检查磁盘容量
     *
     * @return void
     */
    private function CheckDisk()
    {
        //todo
    }

    /**
     * 发送邮件
     *
     * @return void
     */
    private function SendMail()
    {
        if ($this->crond['notice'] == 1 || $this->crond['notice'] == 3) {
            if ($this->code == 0) {
                $subject = $this->code == 0 ? '任务计划执行成功通知' : '任务计划执行失败通知';
                $body = sprintf("任务名称: %s\n当前时间: %s\n", $this->crond['task_name'], date('Y-m-d H:i:s'));

                $result = (new Mail())->SendMail2($subject, $body);
                if ($result['status'] == 1) {
                    print_r(sprintf('邮件发送成功%s', PHP_EOL));
                } else {
                    print_r(sprintf('邮件发送失败[%s]%s', $result['message'], PHP_EOL));
                }
            }
        }

        if ($this->crond['notice'] == 2 || $this->crond['notice'] == 3) {
            if ($this->code != 0) {
                $subject = $this->code == 0 ? '任务计划执行成功通知' : '任务计划执行失败通知';
                $body = sprintf("任务名称: %s\n当前时间: %s\n", $this->crond['task_name'], date('Y-m-d H:i:s'));

                $result = (new Mail())->SendMail2($subject, $body);
                if ($result['status'] == 1) {
                    print_r(sprintf('邮件发送成功%s', PHP_EOL));
                } else {
                    print_r(sprintf('邮件发送失败[%s]%s', $result['message'], PHP_EOL));
                }
            }
        }
    }

    /**
     * 仓库备份[dump-全量]
     *
     * @return void
     */
    public function RepDumpAll()
    {
        $repList = json_decode($this->crond['rep_name'], true);

        if (in_array('-1', $repList)) {
            $repList = $this->database->select('svn_reps', 'rep_name');
            print_r(sprintf('当前模式为备份所有仓库-仓库列表[%s]%s', implode('|', $repList), PHP_EOL));
        } else {
            print_r(sprintf('当前模式为备份部分仓库-仓库列表[%s]%s', implode('|', $repList), PHP_EOL));
        }

        foreach ($repList as $rep) {
            $this->currentRep = $rep;

            clearstatcache();
            if (!is_dir($this->configSvn['rep_base_path'] .  $rep)) {
                print_r(sprintf('仓库[%s]在磁盘中不存在-自动跳过%s', $rep, PHP_EOL));
                continue;
            }

            $prefix = 'rep_' . $rep . '_';
            $backupName = $prefix . date('YmdHis') . '.dump';

            //数量检查
            $backupList = [];
            $fileList = scandir($this->configSvn['backup_base_path']);
            foreach ($fileList as $key => $value) {
                if ($value == '.' || $value == '..' || is_dir($this->configSvn['backup_base_path'] . '/' . $value)) {
                    continue;
                }
                if (substr($value, 0, strlen($prefix)) == $prefix) {
                    $backupList[] = $value;
                }
            }
            if ($this->crond['save_count'] <= count($backupList)) {
                rsort($backupList);
                for ($i = $this->crond['save_count']; $i <= count($backupList); $i++) {
                    print_r(sprintf('删除仓库[%s]多余的备份文件[%s]%s', $rep, $backupList[$i - 1], PHP_EOL));
                    @unlink($this->configSvn['backup_base_path'] . '/' . $backupList[$i - 1]);
                }
            }

            print_r(sprintf('仓库[%s]开始执行备份程序%s', $rep, PHP_EOL));

            $stderrFile = tempnam(sys_get_temp_dir(), 'svnadmin_');

            $cmd = sprintf("'%s' dump '%s' --quiet  > '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $rep, $this->configSvn['backup_base_path'] .  $backupName);
            passthru($cmd . " 2>$stderrFile", $this->code);

            // $cmd = sprintf("'%s' dump '%s' > '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $rep, $this->configSvn['backup_base_path'] .  $backupName);
            // passthru($cmd . " 2>$stderrFile", $this->code);

            if ($this->code == 0) {
                print_r(sprintf('仓库[%s]备份结束%s', $rep, PHP_EOL));
            } else {
                print_r(sprintf('仓库[%s]备份结束-有错误信息[%s]%s', $rep, file_get_contents($stderrFile), PHP_EOL));
            }

            @unlink($stderrFile);

            $this->SendMail();
        }
    }

    /**
     * 仓库备份[dump-增量-deltas]
     *
     * @return void
     */
    public function RepDumpDeltas()
    {
        $repList = json_decode($this->crond['rep_name'], true);

        if (in_array('-1', $repList)) {
            $repList = $this->database->select('svn_reps', 'rep_name');
            print_r(sprintf('当前模式为deltas增量备份所有仓库-仓库列表[%s]%s', implode('|', $repList), PHP_EOL));
        } else {
            print_r(sprintf('当前模式为deltas增量备份部分仓库-仓库列表[%s]%s', implode('|', $repList), PHP_EOL));
        }

        foreach ($repList as $rep) {
            $this->currentRep = $rep;

            clearstatcache();
            if (!is_dir($this->configSvn['rep_base_path'] .  $rep)) {
                print_r(sprintf('仓库[%s]在磁盘中不存在-自动跳过%s', $rep, PHP_EOL));
                continue;
            }

            $prefix = 'rep_' . $rep . '_deltas_';
            $backupName = $prefix . date('YmdHis') . '.dump';

            //数量检查
            $backupList = [];
            $fileList = scandir($this->configSvn['backup_base_path']);
            foreach ($fileList as $key => $value) {
                if ($value == '.' || $value == '..' || is_dir($this->configSvn['backup_base_path'] . '/' . $value)) {
                    continue;
                }
                if (substr($value, 0, strlen($prefix)) == $prefix) {
                    $backupList[] = $value;
                }
            }
            if ($this->crond['save_count'] <= count($backupList)) {
                rsort($backupList);
                for ($i = $this->crond['save_count']; $i <= count($backupList); $i++) {
                    print_r(sprintf('删除仓库[%s]多余的deltas增量备份文件[%s]%s', $rep, $backupList[$i - 1], PHP_EOL));
                    @unlink($this->configSvn['backup_base_path'] . '/' . $backupList[$i - 1]);
                }
            }

            print_r(sprintf('仓库[%s]开始执行deltas增量备份程序%s', $rep, PHP_EOL));

            $stderrFile = tempnam(sys_get_temp_dir(), 'svnadmin_');

            $cmd = sprintf("'%s' dump '%s' --deltas --quiet > '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $rep, $this->configSvn['backup_base_path'] .  $backupName);
            passthru($cmd . " 2>$stderrFile", $this->code);

            // $cmd = sprintf("'%s' dump '%s' > '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $rep, $this->configSvn['backup_base_path'] .  $backupName);
            // passthru($cmd . " 2>$stderrFile", $this->code);

            if ($this->code == 0) {
                print_r(sprintf('仓库[%s]deltas增量备份结束%s', $rep, PHP_EOL));
            } else {
                print_r(sprintf('仓库[%s]deltas增量备份结束-有错误信息[%s]%s', $rep, file_get_contents($stderrFile), PHP_EOL));
            }

            @unlink($stderrFile);

            $this->SendMail();
        }
    }

    /**
     * 仓库备份[hotcopy-全量]
     *
     * @return void
     */
    public function RepHotcopyAll()
    {
    }

    /**
     * 仓库备份[hotcopy-增量]
     *
     * @return void
     */
    public function RepHotcopyPart()
    {
    }

    /**
     * 仓库检查
     *
     * @return void
     */
    public function RepCheck()
    {
        $repList = json_decode($this->crond['rep_name'], true);

        if (in_array('-1', $repList)) {
            $repList = $this->database->select('svn_reps', 'rep_name');
            print_r(sprintf('当前模式为检查所有仓库-仓库列表[%s]%s', implode('|', $repList), PHP_EOL));
        } else {
            print_r(sprintf('当前模式为检查部分仓库-仓库列表[%s]%s', implode('|', $repList), PHP_EOL));
        }

        foreach ($repList as $rep) {
            $this->currentRep = $rep;

            clearstatcache();
            if (!is_dir($this->configSvn['rep_base_path'] .  $rep)) {
                print_r(sprintf('仓库[%s]在磁盘中不存在-自动跳过%s', $rep, PHP_EOL));
                continue;
            }

            print_r(sprintf('仓库[%s]开始执行检查程序%s', $rep, PHP_EOL));

            $stderrFile = tempnam(sys_get_temp_dir(), 'svnadmin_');

            // $cmd = sprintf("'%s' verify --quiet '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $rep);
            // passthru($cmd . " 2>$stderrFile", $this->code);

            $cmd = sprintf("'%s' verify '%s'", $this->configBin['svnadmin'], $this->configSvn['rep_base_path'] .  $rep);
            passthru($cmd, $this->code);

            if ($this->code == 0) {
                print_r(sprintf('仓库[%s]检查结束2%s', $rep, PHP_EOL));
            } else {
                print_r(sprintf('仓库[%s]检查结束-有错误信息[%s]%s', $rep, file_get_contents($stderrFile), PHP_EOL));
            }

            @unlink($stderrFile);

            $this->SendMail();
        }
    }

    /**
     * shell 脚本
     *
     * @return void
     */
    public function Shell()
    {
        print_r(sprintf('脚本[%s]开始执行%s', $this->crond['task_name'], PHP_EOL));

        $stderrFile = tempnam(sys_get_temp_dir(), 'svnadmin_');

        $shellFile = tempnam(sys_get_temp_dir(), 'svnadmin_');
        file_put_contents($shellFile, $this->crond['shell']);
        shell_exec(sprintf("chmod 755 '%s'", $shellFile));

        passthru($shellFile . " 2>$stderrFile", $this->code);

        if ($this->code == 0) {
            print_r(sprintf('脚本[%s]执行结束%s', $this->crond['task_name'], PHP_EOL));
        } else {
            print_r(sprintf('脚本[%s]执行结束-有错误信息[%s]%s', $this->crond['task_name'], file_get_contents($stderrFile), PHP_EOL));
        }

        @unlink($stderrFile);
        @unlink($shellFile);

        $this->SendMail();
    }

    /**
     * 同步用户
     *
     * @return void
     */
    public function SyncUser()
    {
        (new ServiceSvnuser())->SyncUser();
    }

    /**
     * 同步分组
     *
     * @return void
     */
    public function SyncGroup()
    {
        (new ServiceSvngroup())->SyncGroup();
    }

    /**
     * 同步仓库
     *
     * @return void
     */
    public function SyncRep()
    {
        $serviceSvnrep = new ServiceSvnrep();
        $serviceSvnrep->SyncRep2Authz();
        $result = $serviceSvnrep->SyncRep2Db();
        if ($result['status'] != 1) {
            print_r($result['message']);
        }
    }
}

$command = new Command($argc, $argv);

switch ($argv[1]) {
    case 1: //仓库备份[dump-全量]
        $command->RepDumpAll();
        break;
    case 2: //仓库备份[dump-增量-deltas]
        $command->RepDumpDeltas();
        break;
    case 3: //仓库备份[hotcopy-全量]
        $command->RepHotcopyAll();
        break;
    case 4: //仓库备份[hotcopy-增量]
        $command->RepHotcopyPart();
        break;
    case 5: //仓库检查
        $command->RepCheck();
        break;
    case 6: //shell脚本
        $command->Shell();
        break;
    case 7: //同步SVN用户
        $command->SyncUser();
        break;
    case 8: //同步SVN分组
        $command->SyncGroup();
        break;
    case 9: //同步SVN仓库
        $command->SyncRep();
        break;
    default:
        break;
}
