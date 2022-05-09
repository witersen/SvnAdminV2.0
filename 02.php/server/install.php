<?php
/*
 * @Author: witersen
 * @Date: 2022-05-08 13:31:07
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-09 13:06:47
 * @Description: QQ:1801168257
 */

/**
 * 安装和升级程序
 */

/**
 * 将工作模式限制在cli模式
 */
if (!preg_match('/cli/i', php_sapi_name())) {
    exit('require php-cli mode');
}

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/../app/util/Config.php';

require_once BASE_PATH . '/../config/database.php';
require_once BASE_PATH . '/../config/reg.php';
require_once BASE_PATH . '/../config/svn.php';
require_once BASE_PATH . '/../config/update.php';
require_once BASE_PATH . '/../config/version.php';

require_once BASE_PATH . '/../app/function/curl.php';

class Install
{
    private $config_database;
    private $config_reg;
    private $config_svn;
    private $config_update;
    private $config_version;

    private $scripts = [
        [
            'index' => 1,
            'note' => '帮我安装并配置Subversion'
        ],
        [
            'index' => 2,
            'note' => '按照本系统的要求初始化Subversion（针对以其它方式安装的Subversion）'
        ],
        [
            'index' => 3,
            'note' => '检测SVNAdmin的新版本'
        ],
    ];

    function __construct()
    {
        Config::load(BASE_PATH . '/../config/');

        $this->config_database = Config::get('database');
        $this->config_reg = Config::get('reg');
        $this->config_svn = Config::get('svn');
        $this->config_update = Config::get('update');
        $this->config_version = Config::get('version');
    }

    /**
     * 检测SVNAdmin的新版本并选择更新
     */
    function DetectUpdate()
    {
        //拿到升级服务器配置信息
        //对升级服务器地址进行轮询
        //获取当前版本可升级的版本信息

        foreach ($this->config_update['update_server'] as $key1 => $value1) {

            $json = FunCurlRequest($value1['url']);

            if ($json == null) {
                echo '节点 ' . $value1['nodeName'] . ' 访问超时，切换下一节点' . PHP_EOL;
                continue;
            }

            //json => array
            $array = json_decode($json, true);

            $last = $array['version'];

            if ($this->config_version['version'] == $last) {
                exit('当前为最新版：' . $last . PHP_EOL);
            }
            if ($this->config_version['version'] < $last) {
                echo '有新版本：' . $last . PHP_EOL;

                echo '修复内容如下：' . PHP_EOL;
                foreach ($array['fixd']['con'] as $cons) {
                    echo '    [' . $cons['title'] . ']' . ' ' . $cons['content'] . PHP_EOL;
                }

                echo '新增内容如下：' . PHP_EOL;
                foreach ($array['add']['con'] as $cons) {
                    echo '    [' . $cons['title'] . ']' . ' ' . $cons['content'] . PHP_EOL;
                }

                echo '移除内容如下：' . PHP_EOL;
                foreach ($array['remove']['con'] as $cons) {
                    echo '    [' . $cons['title'] . ']' . ' ' . $cons['content'] . PHP_EOL;
                }

                echo "确定要升级到 $last 版本吗[y/n]：";

                $answer = strtolower(trim(fgets(STDIN)));

                if (!in_array($answer, ['y', 'n'])) {
                    exit('不正确的选项！' . PHP_EOL);
                }

                if ($answer == 'n') {
                    exit('已取消！' . PHP_EOL);
                }

                //下载并执行升级脚本
                $packages = $array['update']['download'][$key1]['packages'];
                $forList = array_column($packages, 'for');
                $current = [
                    'source' => $this->config_version['version'],
                    'dest' => $last
                ];
                if (!in_array($current, $forList)) {
                    exit('没有合适的升级包，请尝试直接手动安装最新版！' . PHP_EOL);
                }
                $index = array_search($current, $forList);
                $update_download_url = $packages[$index]['url'];
                $update_zip = FunCurlRequest($update_download_url);
                if ($update_zip == null) {
                    echo '从节点 ' . $value1['nodeName'] . ' 下载升级包超时，切换下一节点' . PHP_EOL;
                    continue;
                }
                file_put_contents(BASE_PATH . '/update.zip', $update_zip);
                shell_exec('unzip ' . BASE_PATH . '/update.zip');
                if (!is_dir(BASE_PATH . '/update')) {
                    exit('解压升级包出错，请尝试手动解压并执行升级程序！' . PHP_EOL);
                }

                echo '正在执行升级程序' . PHP_EOL;

                passthru('php ' . BASE_PATH . '/update/index.php');

                shell_exec(sprintf("cd '%s' && rm -rf ./update && rm -f update.zip", BASE_PATH));

                exit('升级成功！请重启守护进程文件使部分配置文件生效' . PHP_EOL);
            }
        }
    }

    /**
     * 将SVNAdmin加入到开机自启动
     */
    function InitlSVNAdmin()
    {
    }

    /**
     * 将SVNAdmin取消开机自启动
     */
    function UninitSVNAdmin()
    {
    }

    /**
     * 将SVNAdmin加入监控 如果检测到异常退出则自动重启
     */
    function Monitor()
    {
    }

    /**
     * 修改已经安装的Subversion配置以适合SVNAdmin的管理
     */
    function ConfigSubversion()
    {
        /**
         * 1、检测Subversion的安装情况
         */
        //检测是否有正在运行的进程
        $isRun = shell_exec('ps auxf|grep -v "grep"|grep svnserve') == '' ? false : true;

        //检测安装程序是否存在于环境变量
        $installPath = shell_exec('which svnserve');
        $isInstall = shell_exec('whereis svnserve') == 'svnserve:' ? false : true;

        //正在运行中但是没有安装或者subversion的相关程序没有被加入环境变量
        if ($isRun && !$isInstall) {
            exit('需要将Subversion相关程序加入到环境变量后重试！' . PHP_EOL);
        }

        //正在运行中并且subversion的相关程序已经被加入环境变量
        if ($isRun && $isInstall) {
            //停止svnserve
            exit('请先手动停止正在运行的svnserve程序后重试' . PHP_EOL);
        }

        //不在运行中并且没有安装或者subversion的相关程序没有被加入环境变量
        if (!$isRun && !$isInstall) {
            exit('需要安装Subversion或者需要将已安装的Subversion相关程序加入到环境变量后重试！' . PHP_EOL);
        }

        //不在运行中并且subversion的相关程序已经被加入环境变量
        if (!$isRun && $isInstall) {
            //相关文件配置
        }

        /**
         * 相关文件配置
         */
        $templete_path = BASE_PATH . '../templete/';

        echo '创建相关目录' . PHP_EOL;

        //创建SVNAdmin软件配置信息的主目录
        mkdir($this->config_svn['home_path'], 0700, true);

        //创建SVN仓库父目录
        mkdir($this->config_svn['rep_base_path'], 0700, true);

        //创建备份目录
        mkdir($this->config_svn['home_path'], 0700, true);

        //创建日志目录
        mkdir($this->config_svn['home_path'], 0700, true);

        //创建临时数据目录
        mkdir($this->config_svn['home_path'], 0700, true);

        echo '创建相关文件' . PHP_EOL;

        //写入svnserve环境变量文件
        $con_svnserve_env_file = file_get_contents($templete_path . 'svnserve/svnserve');
        $con_svnserve_env_file = sprintf($con_svnserve_env_file, $this->config_svn['rep_base_path'], $this->config_svn['svn_conf_file'], $this->config_svn['svnserve_log_file']);
        file_put_contents($this->config_svn['svnserve_env_file'], $con_svnserve_env_file);

        //写入SVN仓库权限配置文件
        $con_svn_conf_file = file_get_contents($templete_path . 'svnserve/svnserve.confg');
        file_put_contents($this->config_svn['svn_conf_file'], $con_svn_conf_file);

        //写入authz文件
        $con_svn_authz_file = file_get_contents($templete_path . 'svnserve/authz');
        file_put_contents($this->config_svn['svn_authz_file'], $con_svn_authz_file);

        //写入passwd文件
        $con_svn_passwd_file = file_get_contents($templete_path . 'svnserve/passwd');
        file_put_contents($this->config_svn['svn_passwd_file'], $con_svn_passwd_file);

        //创建svnserve运行日志文件
        file_put_contents($this->config_svn['svnserve_log_file'], '');

        //创建pid文件
        file_put_contents($this->config_svn['svnserve_pid_file'], '');

        /**
         * 将svnserve注册为系统服务
         */
        echo '清理之前注册的svnserve服务' . PHP_EOL;

        shell_exec('systemctl stop svnserve.service');
        shell_exec('systemctl disable svnserve.service');

        $con_svnserve_service_file = file_get_contents($templete_path . 'svnserve/svnserve.service');
        $con_svnserve_service_file = sprintf($con_svnserve_service_file, $this->config_svn['svnserve_env_file'], trim($installPath), $this->config_svn['svnserve_pid_file']);
        file_put_contents($this->config_svn['svnserve_service_file'], $con_svnserve_service_file);

        //启动
        echo '开始启动svnserve服务' . PHP_EOL;

        passthru('systemctl start svnserve');

        //开机自启动
        echo '将svnserve服务加入到开机自启动' . PHP_EOL;

        passthru('systemctl enable svnserve');

        //查看状态
        echo 'svnserve安装成功，打印运行状态：' . PHP_EOL;

        passthru('systemctl status svnserve');
    }

    /**
     * 程序入口
     */
    function Run()
    {
        echo '===============SVNAdmin==================' . PHP_EOL;

        foreach ($this->scripts as $value) {
            echo '（' . $value['index'] . '）' . $value['note'] . PHP_EOL;
        }

        echo '===============================================' . PHP_EOL;

        echo '请输入命令编号：';

        $answer = trim(fgets(STDIN));

        echo '===============================================' . PHP_EOL;

        if (!in_array($answer, array_column($this->scripts, 'index'))) {
            exit('错误的命令编号：' . PHP_EOL);
        }

        if ($answer == 1) {
            //帮我安装并配置Subversion

            $shellPath = BASE_PATH . '/../templete/install/WANdisco/';

            if (!is_dir($shellPath)) {
                exit('安装脚本目录不存在！' . PHP_EOL);
            }

            $shell = scandir($shellPath);

            echo '可选择的Subversion版本如下：' . PHP_EOL;

            $noShell = true;
            foreach ($shell as $value) {
                if ($value == '.' || $value == '..') {
                    continue;
                }
                $noShell = false;
                echo $value . PHP_EOL;
            }

            if ($noShell) {
                exit('没有可选的安装脚本！' . PHP_EOL);
            }

            echo '===============================================' . PHP_EOL;

            echo '请注意SVNAdmin支持管理的Subversion版本为1.8+！' . PHP_EOL;

            echo '请输入要安装的Subversion版本（推荐Subversion-1.10）：';

            $answer = trim(fgets(STDIN));

            echo '===============================================' . PHP_EOL;

            if (!file_exists($shellPath . 'subversion_installer_' . $answer . '.sh')) {
                exit('请选择正确的版本！' . PHP_EOL);
            }

            echo '现在开始执行脚本：' . 'subversion_installer_' . $answer . '.sh' . PHP_EOL;

            echo '===============================================' . PHP_EOL;

            passthru('sh ' . $shellPath . 'subversion_installer_' . $answer . '.sh');

            $this->ConfigSubversion();
        } else if ($answer == 2) {
            //按照本系统的要求初始化Subversion（针对以其它方式安装的Subversion）
            $this->ConfigSubversion();
        } else if ($answer == 3) {
            //检测SVNAdmin的新版本
            $this->DetectUpdate();
        }
    }
}

(new Install())->Run();
