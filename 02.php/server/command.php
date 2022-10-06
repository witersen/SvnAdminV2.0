<?php

if (!preg_match('/cli/i', php_sapi_name())) {
    exit('require php-cli mode');
}

//与web入口文件保持一致
define('BASE_PATH', __DIR__ . '/..');

date_default_timezone_set('PRC');

auto_require(BASE_PATH . '/config/');

// auto_require(BASE_PATH . '/app/function/');

// auto_require(BASE_PATH . '/app/util/', true);

auto_require(BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php');

// auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/Exception.php');
// auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/PHPMailer.php');
// auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/src/SMTP.php');
// auto_require(BASE_PATH . '/extension/PHPMailer-6.6.0/language/phpmailer.lang-zh_cn.php');

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

class Command
{
    private $database;

    private $config_database;

    private $config_svn;

    private $config_bin;

    private $argc;

    private $argv;

    function __construct($argc, $argv)
    {
        $this->argc = $argc;
        $this->argv = $argv;

        Config::load(BASE_PATH . '/config/');

        //配置
        $this->config_database = Config::get('database');
        $this->config_svn = Config::get('svn');
        $this->config_bin =  Config::get('bin');

        //数据库连接
        if (array_key_exists('database_file', $this->config_database)) {
            $this->config_database['database_file'] = sprintf($this->config_database['database_file'], $this->config_svn['home_path']);
        }
        $this->database = new Medoo($this->config_database);
    }

    /**
     * 仓库备份[dump-全量]
     *
     * @return void
     */
    public function RepDumpAll()
    {
        //查询参数详情
        $crond = $this->database->get('crond', '*', [
            'sign' => $this->argv[2]
        ]);

        if (empty($crond)) {
            print_r(sprintf('数据库中没有与标识[%s]相匹配的任务计划-自动退出%s', $this->argv[2], PHP_EOL));
            exit;
        }

        //检查是否为备份仓库
        if (!in_array($crond['task_type'], [1, 2, 3, 4])) {
            print_r(sprintf('当前任务计划类型[%s]不为备份仓库-自动退出%s', $crond['task_type'], PHP_EOL));
            exit;
        }

        $crond['rep_name'] = json_decode($crond['rep_name'], true);

        //备份仓库列表
        $repList = $crond['rep_name'];
        if (in_array('-1', $repList)) {
            $repList = $this->database->select('svn_reps', 'rep_name');
            print_r(sprintf('当前模式为备份所有仓库-仓库列表[%s]%s', implode('|', $repList), PHP_EOL));
        } else {
            print_r(sprintf('当前模式为备份部分仓库-仓库列表[%s]%s', implode('|', $repList), PHP_EOL));
        }

        //检查磁盘容量 todo

        //备份模式
        switch ($crond['task_type']) {
            case 1: //仓库备份[dump-全量]
                foreach ($repList as $rep) {
                    //检查仓库是否存在
                    clearstatcache();
                    if (!is_dir($this->config_svn['rep_base_path'] .  $rep)) {
                        print_r(sprintf('仓库[%s]在磁盘中不存在-自动跳过%s', $rep, PHP_EOL));
                        continue;
                    }

                    $prefix = 'rep_' . $rep . '_';
                    $backupName = $prefix . date('YmdHis') . '.dump';

                    //数量检查
                    $backupList = [];
                    $fileList = scandir($this->config_svn['backup_base_path']);
                    foreach ($fileList as $key => $value) {
                        if ($value == '.' || $value == '..' || is_dir($this->config_svn['backup_base_path'] . '/' . $value)) {
                            continue;
                        }
                        if (substr($value, 0, strlen($prefix)) == $prefix) {
                            $backupList[] = $value;
                        }
                    }
                    if ($crond['save_count'] <= count($backupList)) {
                        //删除多余的备份
                        rsort($backupList);
                        for ($i = $crond['save_count']; $i <= count($backupList); $i++) {
                            print_r(sprintf('删除仓库[%s]多余的备份文件[%s]%s', $rep, $backupList[$i - 1], PHP_EOL));
                            unlink($this->config_svn['backup_base_path'] . '/' . $backupList[$i - 1]);
                        }
                    }

                    //开始备份
                    print_r(sprintf('仓库[%s]开始执行备份程序%s', $rep, PHP_EOL));

                    //定义错误输出文件路径
                    $stderrFile = tempnam('/tmp', 'svnadmin_');

                    //将标准错误重定向到文件
                    //使用状态码来标识错误信息
                    $cmd = sprintf("'%s' dump '%s' --quiet  > '%s'", $this->config_bin['svnadmin'], $this->config_svn['rep_base_path'] .  $rep, $this->config_svn['backup_base_path'] .  $backupName);
                    passthru($cmd . " 2>$stderrFile", $code);

                    if ($code == 0) {
                        print_r(sprintf('仓库[%s]备份结束%s', $rep, PHP_EOL));
                    } else {
                        print_r(sprintf('仓库[%s]备份结束-有错误信息[%s]%s', $rep, file_get_contents($stderrFile), PHP_EOL));
                    }

                    unlink($stderrFile);

                    if ($crond['notice'] == 1 || $crond['notice'] == 3) {
                        if ($code == 0) {
                            //成功通知
                            $subject = "任务计划执行成功通知";
                            $body = sprintf("任务名称: %s\n当前时间: %s\n", $crond['task_name'], date('Y-m-d H:i:s'));

                            $result = (new Mail())->SendMail2($subject, $body);
                            if ($result['status'] == 1) {
                                print_r(sprintf('仓库[%s]备份结束-发送邮件通知信息成功%s', $rep, PHP_EOL));
                            } else {
                                print_r(sprintf('仓库[%s]备份结束-发送邮件通知信息失败-[%s]%s', $rep, $result['message'], PHP_EOL));
                            }
                        }
                    }

                    if ($crond['notice'] == 2 || $crond['notice'] == 3) {
                        if ($code != 0) {
                            //失败通知
                            $subject = "任务计划执行失败通知";
                            $body = sprintf("任务名称: %s\n当前时间: %s\n", $crond['task_name'], date('Y-m-d H:i:s'));
                            $result = (new Mail())->SendMail2($subject, $body);
                            if ($result['status'] == 1) {
                                print_r(sprintf('仓库[%s]备份结束-发送邮件通知信息成功%s', $rep, PHP_EOL));
                            } else {
                                print_r(sprintf('仓库[%s]备份结束-发送邮件通知信息失败-[%s]%s', $rep, $result['message'], PHP_EOL));
                            }
                        }
                    }
                }
                break;
            case 2: //仓库备份[dump-增量]
                break;
            case 3: //仓库备份[hotcopy-全量]
                break;
            case 4: //仓库备份[hotcopy-增量]
                break;
            case 5: //仓库检查
                break;
            case 6: //shell脚本
                break;
            default:
                break;
        }

        //处理信息通知

    }

    /**
     * 仓库备份[dump-增量]
     *
     * @return void
     */
    public function RepDumpPart()
    {
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
    }
}

//接收参数 校验参数
if (!isset($argv[1]) || !isset($argv[2])) {
    print_r(sprintf('参数不完整-自动退出%s', PHP_EOL));
    exit;
}

$command = new Command($argc, $argv);

switch ($argv[1]) {
    case 1: //仓库备份[dump-全量]
        $command->RepDumpAll();
        break;
    case 2: //仓库备份[dump-增量]
        $command->RepDumpPart();
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
        break;
    default:
        break;
}
