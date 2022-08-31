<?php
/*
 * @Author: witersen
 * @Date: 2022-05-08 13:31:07
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-12 18:38:26
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
    private $config_bin;

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
        [
            'index' => 4,
            'note' => '修改当前的数据存储主目录'
        ]
    ];

    function __construct()
    {
        Config::load(BASE_PATH . '/../config/');

        $this->config_database = Config::get('database');
        $this->config_reg = Config::get('reg');
        $this->config_svn = Config::get('svn');
        $this->config_update = Config::get('update');
        $this->config_version = Config::get('version');
        $this->config_bin = Config::get('bin');
    }

    /**
     * 由于array_column到php5.5+才支持
     * 为了兼容php5.4
     * 这里选择手动实现 可能性能不高
     */
    function array_column($array, $columnKey)
    {
        $resultArray = [];
        foreach ($array as $key => $value) {
            if (!array_key_exists($columnKey, $value)) {
                return false;
            }
            array_push($resultArray, $value[$columnKey]);
        }
        return $resultArray;
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
                echo '===============================================' . PHP_EOL;
                continue;
            }

            //json => array
            $array = json_decode($json, true);

            $last = $array['version'];

            if ($this->config_version['version'] == $last) {
                echo '当前为最新版：' . $last . PHP_EOL;
                echo '===============================================' . PHP_EOL;
                exit();
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
                    echo '不正确的选项！'  . PHP_EOL;
                    echo '===============================================' . PHP_EOL;
                    exit();
                }

                if ($answer == 'n') {
                    echo '已取消！' . PHP_EOL;
                    echo '===============================================' . PHP_EOL;
                    exit();
                }

                //下载并执行升级脚本
                $packages = $array['update']['download'][$key1]['packages'];
                $forList = $this->array_column($packages, 'for');
                $current = [
                    'source' => $this->config_version['version'],
                    'dest' => $last
                ];
                if (!in_array($current, $forList)) {
                    echo '没有合适的升级包，请尝试直接手动安装最新版！' . PHP_EOL;
                    echo '===============================================' . PHP_EOL;
                    exit();
                }
                $index = array_search($current, $forList);
                $update_download_url = $packages[$index]['url'];
                $update_zip = FunCurlRequest($update_download_url);
                if ($update_zip == null) {
                    echo '从节点 ' . $value1['nodeName'] . ' 下载升级包超时，切换下一节点' . PHP_EOL;
                    echo '===============================================' . PHP_EOL;
                    continue;
                }
                file_put_contents(BASE_PATH . '/update.zip', $update_zip);
                passthru('unzip ' . BASE_PATH . '/update.zip');
                if (!is_dir(BASE_PATH . '/update')) {
                    echo '解压升级包出错，请尝试手动解压并执行升级程序！' . PHP_EOL;
                    echo '===============================================' . PHP_EOL;
                    exit();
                }

                echo '正在执行升级程序' . PHP_EOL;

                passthru('php ' . BASE_PATH . '/update/index.php');

                passthru(sprintf("cd '%s' && rm -rf ./update && rm -f update.zip", BASE_PATH));

                echo '升级成功！请重启守护进程文件使部分配置文件生效' . PHP_EOL;
                echo '===============================================' . PHP_EOL;
                exit();
            }
            if ($this->config_version['version'] > $last) {
                echo '当前版本高于已发布版本' . PHP_EOL;
                echo '===============================================' . PHP_EOL;
                exit();
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
     * 获取Linux操作系统类型
     * 
     * /etc/redhat-release      redhat 或 centos 或 rocky
     * /etc/debian_version      debian 或 ubuntu
     * /etc/slackware_version   Slackware
     * /etc/lsb-release         ubuntu
     */
    private function GetOS()
    {
        if (PHP_OS != 'Linux') {
            return false;
        }
        if (file_exists('/etc/redhat-release')) {
            $readhat_release = file_get_contents('/etc/redhat-release');
            $readhat_release = strtolower($readhat_release);
            if (strstr($readhat_release, 'centos')) {
                if (strstr($readhat_release, '8.')) {
                    return 'centos 8';
                } else if (strstr($readhat_release, '7.')) {
                    return 'centos 7';
                } else {
                    return false;
                }
            } else if (strstr($readhat_release, 'rocky')) {
                return 'rocky';
            } else {
                return false;
            }
        } else if (file_exists('/etc/lsb-release')) {
            return 'ubuntu';
        } else {
            return false;
        }
    }

    /**
     * 检测目标路径是否为空
     *
     * @return bool
     */
    private function IsDirEmpty($path)
    {
        clearstatcache();

        $filename = scandir($path);

        foreach ($filename as $key => $value) {
            if ($value != '.' && $value != '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * 修改已经安装的Subversion配置以适合SVNAdmin的管理
     */
    function ConfigSubversion()
    {
        echo PHP_EOL . '===============================================' . PHP_EOL;
        echo '确定要开始配置Subversion程序吗[y/n]：';
        $continue = strtolower(trim(fgets(STDIN)));

        if (!in_array($continue, ['y', 'n'])) {
            echo '不正确的选项！'  . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        if ($continue == 'n') {
            echo '已取消！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        /**
         * 1、检测Subversion的安装情况
         */
        //检测是否有正在运行的进程
        if (shell_exec('ps auxf|grep -v "grep"|grep svnserve') != '') {
            echo '请先手动停止正在运行的 svnserve 程序后重试！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        /**
         * 2、令用户手动选择配置程序的路径
         */
        $needBin = [
            'svn' => '',
            'svnadmin' => '',
            'svnlook' => '',
            'svnserve' => '',
            'svnversion' => '',
            'svnsync' => '',
            'svnrdump' => '',
            'svndumpfilter' => '',
            'svnmucc' => ''
        ];

        echo '===============================================' . PHP_EOL;
        echo '开始配置Subversion程序！' . PHP_EOL;
        echo '===============================================' . PHP_EOL;

        foreach ($needBin as $key => $value) {
            echo "请输入 $key 程序位置：" . PHP_EOL;
            echo '自动检测到以下程序路径：' . PHP_EOL;
            passthru("which $key 2>/dev/null");
            echo '请输入回车使用默认检测路径或手动输入：';
            $binPath = fgets(STDIN);
            if ($binPath == '') {
                echo '输入不能为空！' . PHP_EOL;
                echo '===============================================' . PHP_EOL;
                exit();
            }
            if ($binPath == "\n") {
                $binPath = trim(shell_exec("which $key 2>/dev/null"));
                if ($binPath == '') {
                    if ($key == 'svnmucc') {
                        echo "未检测到 $key ，请手动输入程序路径！" . PHP_EOL;
                        echo "由于 $key 在当前版本非必要，因此无安装可忽略" . PHP_EOL;
                        echo '===============================================' . PHP_EOL;
                    } else {
                        echo "未检测到 $key ，请手动输入程序路径！" . PHP_EOL;
                        echo '===============================================' . PHP_EOL;
                        exit();
                    }
                }
            } else {
                $binPath = trim($binPath);
            }
            echo "$key 程序位置：$binPath" . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            $needBin[$key] = $binPath;
        }

        $binCon = <<<CON
        <?php
        
        return [
            'svn' => '{$needBin['svn']}',
            'svnadmin' => '{$needBin['svnadmin']}',
            'svnlook' => '{$needBin['svnlook']}',
            'svnserve' => '{$needBin['svnserve']}',
            'svnversion' => '{$needBin['svnversion']}',
            'svnsync' => '{$needBin['svnsync']}',
            'svnrdump' => '{$needBin['svnrdump']}',
            'svndumpfilter' => '{$needBin['svndumpfilter']}',
            'svnmucc' => '{$needBin['svnmucc']}'
        ];
CON;

        file_put_contents(BASE_PATH . '/../config/bin.php', $binCon);

        /**
         * 3、相关文件配置
         */
        $templete_path = BASE_PATH . '/../templete/';

        echo '创建相关目录' . PHP_EOL;

        clearstatcache();

        //创建SVNAdmin软件配置信息的主目录
        is_dir($this->config_svn['home_path']) ? '' : mkdir($this->config_svn['home_path'], 0700, true);

        //创建SVN仓库父目录
        is_dir($this->config_svn['rep_base_path']) ? '' : mkdir($this->config_svn['rep_base_path'], 0700, true);

        //创建推荐钩子目录
        is_dir($this->config_svn['recommend_hook_path']) ? '' : mkdir($this->config_svn['recommend_hook_path'], 0700, true);
        shell_exec(sprintf("cp -r '%s' '%s'", $templete_path . '/hooks', $this->config_svn['home_path']));

        //创建备份目录
        is_dir($this->config_svn['backup_base_path']) ? '' : mkdir($this->config_svn['backup_base_path'], 0700, true);

        //创建日志目录
        is_dir($this->config_svn['log_base_path']) ? '' : mkdir($this->config_svn['log_base_path'], 0700, true);

        //创建临时数据目录
        is_dir($this->config_svn['temp_base_path']) ? '' : mkdir($this->config_svn['temp_base_path'], 0700, true);

        //创建模板文件目录
        is_dir($this->config_svn['templete_base_path']) ? '' : mkdir($this->config_svn['templete_base_path'], 0700, true);

        //创建仓库结构模板目录
        // is_dir($this->config_svn['templete_init_struct']) ? '' : mkdir($this->config_svn['templete_init_struct'], 0700, true);
        shell_exec(sprintf("cp -r '%s' '%s'", $templete_path . '/initStruct', $this->config_svn['templete_base_path']));

        echo '===============================================' . PHP_EOL;

        echo '创建相关文件' . PHP_EOL;

        //写入svnserve环境变量文件
        $con_svnserve_env_file = file_get_contents($templete_path . 'svnserve/svnserve');
        $con_svnserve_env_file = sprintf($con_svnserve_env_file, $this->config_svn['rep_base_path'], $this->config_svn['svn_conf_file'], $this->config_svn['svnserve_log_file']);
        file_put_contents($this->config_svn['svnserve_env_file'], $con_svnserve_env_file);

        //写入SVN仓库权限配置文件
        $con_svn_conf_file = file_get_contents($templete_path . 'svnserve/svnserve.conf');
        file_put_contents($this->config_svn['svn_conf_file'], $con_svn_conf_file);

        //写入authz文件
        $con_svn_authz_file = file_get_contents($templete_path . 'svnserve/authz');
        if (file_exists($this->config_svn['svn_authz_file'])) {
            echo PHP_EOL . '===============================================' . PHP_EOL;
            echo '要覆盖原有的权限配置文件 authz 吗？[y/n]：';
            $continue = strtolower(trim(fgets(STDIN)));

            if (!in_array($continue, ['y', 'n'])) {
                echo '不正确的选项！'  . PHP_EOL;
                echo '===============================================' . PHP_EOL;
                exit();
            }

            if ($continue == 'y') {
                //备份
                copy($this->config_svn['svn_authz_file'], $this->config_svn['home_path'] . time() . 'authz');
                //操作
                file_put_contents($this->config_svn['svn_authz_file'], $con_svn_authz_file);
            }
        } else {
            file_put_contents($this->config_svn['svn_authz_file'], $con_svn_authz_file);
        }

        //写入passwd文件
        $con_svn_passwd_file = file_get_contents($templete_path . 'svnserve/passwd');
        if (file_exists($this->config_svn['svn_passwd_file'])) {
            echo PHP_EOL . '===============================================' . PHP_EOL;
            echo '要覆盖原有的权限配置文件 passwd 吗？[y/n]：';
            $continue = strtolower(trim(fgets(STDIN)));

            if (!in_array($continue, ['y', 'n'])) {
                echo '不正确的选项！'  . PHP_EOL;
                echo '===============================================' . PHP_EOL;
                exit();
            }

            if ($continue == 'y') {
                //备份
                copy($this->config_svn['svn_passwd_file'], $this->config_svn['home_path'] . time() . 'passwd');
                //操作
                file_put_contents($this->config_svn['svn_passwd_file'], $con_svn_passwd_file);
            }
        } else {
            file_put_contents($this->config_svn['svn_passwd_file'], $con_svn_passwd_file);
        }

        //创建svnserve运行日志文件
        file_put_contents($this->config_svn['svnserve_log_file'], '');

        //创建pid文件
        file_put_contents($this->config_svn['svnserve_pid_file'], '');

        echo '===============================================' . PHP_EOL;

        /**
         * 4、关闭selinux 
         * 包括临时关闭和永久关闭
         */
        echo '临时关闭并永久关闭seliux' . PHP_EOL;

        //临时关闭selinux
        shell_exec('setenforce 0');

        //永久关闭selinux
        shell_exec("sed -i 's/SELINUX=enforcing/SELINUX=disabled/g' /etc/selinux/config");

        echo '===============================================' . PHP_EOL;

        /**
         * 5、配置SQLite数据库文件
         */
        echo '配置并启用SQLite数据库' . PHP_EOL;

        if (file_exists($this->config_svn['home_path'] . 'svnadmin.db')) {
            echo PHP_EOL . '===============================================' . PHP_EOL;
            echo '要覆盖原有的SQLite数据库文件 svnadmin.db 吗？[y/n]：';
            $continue = strtolower(trim(fgets(STDIN)));

            if (!in_array($continue, ['y', 'n'])) {
                echo '不正确的选项！'  . PHP_EOL;
                echo '===============================================' . PHP_EOL;
                exit();
            }

            if ($continue == 'y') {
                //备份
                copy($this->config_svn['home_path'] . 'svnadmin.db', $this->config_svn['home_path'] . time() . 'svnadmin.db');
                //操作
                copy($templete_path . '/database/sqlite/svnadmin.db', $this->config_svn['home_path'] . 'svnadmin.db');
            }
        } else {
            copy($templete_path . '/database/sqlite/svnadmin.db', $this->config_svn['home_path'] . 'svnadmin.db');
        }

        //配置SQLite数据库文件的父目录权限配置为777 解决无法写入且不报错的问题
        shell_exec('chmod 777 ' . $this->config_svn['home_path']);

        echo '===============================================' . PHP_EOL;

        /**
         * 6、主目录授权
         */
        echo '配置主目录权限' . PHP_EOL;

        shell_exec(sprintf("chmod 777 -R '%s'", $this->config_svn['home_path']));

        echo '===============================================' . PHP_EOL;

        /**
         * 7、将svnserve注册为系统服务
         */
        echo '清理之前注册的svnserve服务' . PHP_EOL;

        passthru('systemctl stop svnserve.service');
        passthru('systemctl disable svnserve.service');
        passthru('systemctl daemon-reload');

        echo '===============================================' . PHP_EOL;

        echo '注册新的svnserve服务' . PHP_EOL;

        $os = $this->GetOS();
        $con_svnserve_service_file = file_get_contents($templete_path . 'svnserve/svnserve.service');
        $con_svnserve_service_file = sprintf($con_svnserve_service_file, $this->config_svn['svnserve_env_file'], $needBin['svnserve'], $this->config_svn['svnserve_pid_file']);
        if ($os == 'centos 7' || $os == 'centos 8') {
            file_put_contents($this->config_svn['svnserve_service_file']['centos'], $con_svnserve_service_file);
        } else if ($os == 'ubuntu') {
            file_put_contents($this->config_svn['svnserve_service_file']['ubuntu'], $con_svnserve_service_file);
        } else if ($os == 'rocky') {
            file_put_contents($this->config_svn['svnserve_service_file']['centos'], $con_svnserve_service_file);
        } else {
            file_put_contents($this->config_svn['svnserve_service_file']['centos'], $con_svnserve_service_file);
            echo '===============================================' . PHP_EOL;
            echo '警告！当前操作系统版本未测试，使用过程中可能会遇到问题！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
        }

        echo '===============================================' . PHP_EOL;

        //启动
        echo '开始启动svnserve服务' . PHP_EOL;

        passthru('systemctl daemon-reload');
        passthru('systemctl start svnserve');

        echo '===============================================' . PHP_EOL;

        //开机自启动
        echo '将svnserve服务加入到开机自启动' . PHP_EOL;

        passthru('systemctl enable svnserve');

        echo '===============================================' . PHP_EOL;

        //查看状态
        echo 'svnserve安装成功，打印运行状态：' . PHP_EOL;

        passthru('systemctl status svnserve');

        echo '===============================================' . PHP_EOL;
    }

    /**
     * 修改当前的数据存储主目录
     */
    function MoveHome()
    {
        //检查是否停止了svnserve
        if (shell_exec('ps auxf|grep -v "grep"|grep svnserve') != '') {
            echo '请先手动停止正在运行的 svnserve 程序后重试！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        //输入路径
        echo '请输入目标目录的绝对路径：';
        $newHomePath = trim(fgets(STDIN));
        if ($newHomePath == '') {
            echo '===============================================' . PHP_EOL;
            echo '输入不能为空！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        //检查要修改的目标路径是否存在
        clearstatcache();
        if (!is_dir($newHomePath)) {
            echo '===============================================' . PHP_EOL;
            echo '目标目录不存在！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        //路径是否相同
        if ($newHomePath == $this->config_svn['home_path']) {
            echo '===============================================' . PHP_EOL;
            echo '路径无变化！'  . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        //检查目标路径是否为空
        if (!$this->IsDirEmpty($newHomePath)) {
            echo '===============================================' . PHP_EOL;
            echo '目标目录需要为空！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        echo '===============================================' . PHP_EOL;
        echo '提醒！该步骤适用于您之前执行过 [1] 或 [2] 步骤进行过初始化配置的情况' . PHP_EOL;

        echo '===============================================' . PHP_EOL;
        echo '提醒！不建议将数据存储主目录移动到 root 目录下，因为这会导致读取权限出现问题（除非将 root 目录设置 777 ，但是也不是好主意）' . PHP_EOL;

        //对输入的路径规范化，如果末尾没有带有 / 则自动补全
        if (substr($newHomePath, strlen($newHomePath) - 1) != '/') {
            $newHomePath .= '/';
        }

        //再次确认
        echo '===============================================' . PHP_EOL;
        echo sprintf('将数据存储主目录从 %s 修改为 %s', $this->config_svn['home_path'], $newHomePath) . PHP_EOL;
        echo '===============================================' . PHP_EOL;
        echo '确定要继续操作吗[y/n]：';
        $continue = strtolower(trim(fgets(STDIN)));
        echo '===============================================' . PHP_EOL;

        if (!in_array($continue, ['y', 'n'])) {
            echo '不正确的选项！'  . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        if ($continue == 'n') {
            echo '已取消！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
            exit();
        }

        //旧内容
        $old_config_svn = $this->config_svn;

        //修改配置文件 svn.php
        $con = file_get_contents(BASE_PATH . '/../config/svn.php');
        $con  = preg_replace("/\\\$home_path[\s]*=[\s]*(['\"])(.*)\\1[;]/", sprintf("\$home_path = '%s';", $newHomePath), $con);
        //判断是否匹配成功
        file_put_contents(BASE_PATH . '/../config/svn.php', $con);

        //新内容
        $new_config_svn = Config::get('svn');

        //修改svnserve文件中的仓库路径、配置文件路径、日志文件路径
        echo '修改svnserve环境变量文件' . PHP_EOL;

        $templete_path = BASE_PATH . '/../templete/';
        $con_svnserve_env_file = file_get_contents($templete_path . 'svnserve/svnserve');
        $con_svnserve_env_file = sprintf($con_svnserve_env_file, $new_config_svn['rep_base_path'], $new_config_svn['svn_conf_file'], $new_config_svn['svnserve_log_file']);
        file_put_contents($old_config_svn['svnserve_env_file'], $con_svnserve_env_file);

        echo '===============================================' . PHP_EOL;

        //开始移动主目录
        echo '开始移动主目录' . PHP_EOL;

        passthru(sprintf("mv %s* %s", $old_config_svn['home_path'], $new_config_svn['home_path']));

        echo '===============================================' . PHP_EOL;

        //为新目录授权
        echo '为新目录授权' . PHP_EOL;

        shell_exec('chmod 777 -R ' . $new_config_svn['home_path']);

        echo '===============================================' . PHP_EOL;

        echo '清理之前注册的svnserve服务' . PHP_EOL;

        passthru('systemctl stop svnserve.service');
        passthru('systemctl disable svnserve.service');
        passthru('systemctl daemon-reload');

        echo '===============================================' . PHP_EOL;

        echo '注册新的svnserve服务' . PHP_EOL;

        $os = $this->GetOS();
        $con_svnserve_service_file = file_get_contents($templete_path . 'svnserve/svnserve.service');
        $con_svnserve_service_file = sprintf($con_svnserve_service_file, $new_config_svn['svnserve_env_file'], $this->config_bin['svnserve'], $new_config_svn['svnserve_pid_file']);
        if ($os == 'centos 7' || $os == 'centos 8') {
            file_put_contents($new_config_svn['svnserve_service_file']['centos'], $con_svnserve_service_file);
        } else if ($os == 'ubuntu') {
            file_put_contents($new_config_svn['svnserve_service_file']['ubuntu'], $con_svnserve_service_file);
        } else if ($os == 'rocky') {
            file_put_contents($new_config_svn['svnserve_service_file']['centos'], $con_svnserve_service_file);
        } else {
            file_put_contents($new_config_svn['svnserve_service_file']['centos'], $con_svnserve_service_file);
            echo '===============================================' . PHP_EOL;
            echo '警告！当前操作系统版本未测试，使用过程中可能会遇到问题！' . PHP_EOL;
            echo '===============================================' . PHP_EOL;
        }

        echo '===============================================' . PHP_EOL;

        //启动
        echo '开始启动svnserve服务' . PHP_EOL;

        passthru('systemctl daemon-reload');
        passthru('systemctl start svnserve');

        echo '===============================================' . PHP_EOL;

        //开机自启动
        echo '将svnserve服务加入到开机自启动' . PHP_EOL;

        passthru('systemctl enable svnserve');

        echo '===============================================' . PHP_EOL;

        //查看状态
        echo 'svnserve重新配置成功，打印运行状态：' . PHP_EOL;

        passthru('systemctl status svnserve');

        echo '===============================================' . PHP_EOL;

        //重启守护进程
        echo '请运行 svnadmind.php 程序手动重启后台程序！' . PHP_EOL;

        passthru('php svnadmind.php stop');

        echo '===============================================' . PHP_EOL;

        //sqlite数据库
    }

    /**
     * 程序入口
     */
    function Run()
    {
        echo '===============SVNAdmin==================' . PHP_EOL;

        foreach ($this->scripts as $value) {
            echo '[' . $value['index'] . '] ' . $value['note'] . PHP_EOL;
        }

        echo '===============================================' . PHP_EOL;

        echo '请输入命令编号：';

        $answer = trim(fgets(STDIN));

        echo '===============================================' . PHP_EOL;

        if (!in_array($answer, $this->array_column($this->scripts, 'index'))) {
            exit('错误的命令编号：' . PHP_EOL);
        }

        if ($answer == 1) {
            //帮我安装并配置Subversion

            $shellPath = BASE_PATH . '/../templete/install/WANdisco/';

            if (!is_dir($shellPath)) {
                exit('安装脚本目录不存在！' . PHP_EOL);
            }

            $shell = scandir($shellPath);

            echo '| Subversion安装脚本来自 WANdiso' . PHP_EOL;

            echo '| 当前提供的安装脚本不一定适配所有操作系统！如部分的ubuntu和rokcy等' . PHP_EOL;

            echo '| 如果当前操作系统平台提供的Subversion版本较低（<1.8）才推荐使用此方法安装Subversion！' . PHP_EOL;

            echo '| 如果由于网络延迟原因安装失败，可手动停止后多尝试几次' . PHP_EOL;

            echo '| 在通过脚本安装Subversion的过程中，请注意信息交互！' . PHP_EOL;

            echo '===============================================' . PHP_EOL;

            echo '可选择的Subversion版本如下：' . PHP_EOL;

            echo '===============================================' . PHP_EOL;

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

            echo '===============================================' . PHP_EOL;

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
        } else if ($answer == 4) {
            //修改当前的数据存储主目录
            $this->MoveHome();
        }
    }
}

//检测禁用函数
$require_functions = ['shell_exec', 'passthru'];
$disable_functions = explode(',', ini_get('disable_functions'));
foreach ($disable_functions as $disable) {
    if (in_array(trim($disable), $require_functions)) {
        echo "需要的 $disable 函数被禁用" . PHP_EOL;
        exit();
    }
}

(new Install())->Run();
