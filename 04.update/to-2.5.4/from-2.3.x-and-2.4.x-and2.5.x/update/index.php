<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

//限制工作模式
if (!preg_match('/cli/i', php_sapi_name())) {
    exit('require php-cli mode' . PHP_EOL);
}

//调试
ini_set('display_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('PRC');

define('BASE_PATH', dirname(dirname(__DIR__)));

if (!file_exists(BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php')) {
    echo sprintf('找不到文件[%s]确认当前是否处于[server]目录下%s', BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php', PHP_EOL);
    exit;
}

require_once BASE_PATH . '/extension/Medoo-1.7.10/src/Medoo.php';

use Medoo\Medoo;

class Update
{
    private $config_files = [
        'bin.php',
        'daemon.php',
        'database.php',
        'reg.php',
        'router.php',
        'sign.php',
        'svn.php',
        'update.php',
        'version.php'
    ];

    private $require_functions = [
        'shell_exec',
        'passthru'
    ];

    private $database;

    private $database_type = 'mysql';

    function __construct()
    {
        //配置文件检查
        $this->check_config();

        //配置目录加载
        Config::load(BASE_PATH . '/config/');

        //初始化数据库连接
        $configDatabase = Config::get('database');
        $configSvn = Config::get('svn');
        if (array_key_exists('database_file', $configDatabase)) {
            $this->database_type = 'sqlite';
            $configDatabase['database_file'] = sprintf($configDatabase['database_file'], $configSvn['home_path']);
        }
        try {
            $this->database = new Medoo($configDatabase);
        } catch (\Exception $e) {
            print_r($e->getMessage());
            exit;
        }
    }

    //更新
    public function update()
    {
        $this->warning_extension();

        //禁用函数检查
        $this->check_disable_function();

        //升级数据库
        $this->update_database();

        //升级配置文件
        $this->update_config();

        $this->warning_extension();
    }

    //数据库升级
    private function update_database()
    {
        sleep(1);

        if ($this->database_type == 'mysql') {
            //表 admin_users 增加字段 admin_user_token
            $this->database->query("ALTER TABLE `admin_users` ADD COLUMN `admin_user_token` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户当前token' AFTER `admin_user_email`;");
            $this->check_column_exist('admin_users', 'admin_user_token');

            //增加表 tasks
            $this->database->query("CREATE TABLE `tasks` (
                `task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `task_name` varchar(1000) NOT NULL,
                `task_status` tinyint(1) NOT NULL COMMENT '1 待执行\r\n2 执行中\r\n3 已完成\r\n4 已取消\r\n5 意外中断',
                `task_cmd` varchar(5000) NOT NULL,
                `task_type` varchar(255) NOT NULL,
                `task_unique` varchar(255) NOT NULL,
                `task_log_file` varchar(5000) DEFAULT NULL,
                `task_optional` varchar(5000) DEFAULT NULL,
                `task_create_time` varchar(45) NOT NULL,
                `task_update_time` varchar(45) DEFAULT NULL,
                PRIMARY KEY (`task_id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
            $this->check_table_exist('tasks');

            //增加表 crond
            $this->database->query("CREATE TABLE `crond`  (
            `crond_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `sign` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'shell文件和日志文件唯一标识',
            `task_type` int(11) UNSIGNED NOT NULL COMMENT '任务计划类型\r\n\r\n1 仓库备份[dump-全量]\r\n2 仓库备份[dump-增量]\r\n3 仓库备份[hotcopy-全量]\r\n4 仓库备份[hotcopy-增量]\r\n5 仓库检查\r\n6 shell脚本',
            `task_name` varchar(450) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '任务名称',
            `cycle_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '周期类型\r\n\r\nminute 每分钟\r\nminute_n 每隔N分钟\r\nhour 每小时\r\nhour_n 每隔N小时\r\nday 每天\r\nday_n 每隔N天\r\nweek 每周\r\nmonth 每月',
            `cycle_desc` varchar(450) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '执行周期描述',
            `status` int(11) UNSIGNED NOT NULL COMMENT '启用状态',
            `save_count` int(11) UNSIGNED NOT NULL COMMENT '保存数量',
            `rep_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '操作仓库列表',
            `week` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '周',
            `day` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '天或日',
            `hour` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '小时',
            `minute` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '分钟',
            `notice` int(11) UNSIGNED NOT NULL COMMENT '0 关闭通知 1 成功通知 2 失败通知 3 全部通知',
            `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '任务计划表达式',
            `shell` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '自定义脚本',
            `last_exec_time` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '上次执行时间',
            `create_time` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '添加时间',
            PRIMARY KEY (`crond_id`) USING BTREE
          ) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;");
            $this->check_table_exist('crond');

            //增加表 subadmin
            $this->database->query("CREATE TABLE `subadmin`  (
            `subadmin_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `subadmin_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            `subadmin_password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            `subadmin_phone` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
            `subadmin_email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
            `subadmin_status` int(255) NOT NULL,
            `subadmin_note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
            `subadmin_last_login` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
            `subadmin_create_time` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            `subadmin_tree` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            `subadmin_functions` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            `subadmin_token` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
            PRIMARY KEY (`subadmin_id`) USING BTREE
          ) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;");
            $this->check_table_exist('subadmin');

            //表 svn_groups 增加字段 include_aliase_count
            $this->database->query("ALTER TABLE `svn_groups` ADD COLUMN `include_aliase_count` int(11) NOT NULL AFTER `include_group_count`;");
            $this->check_column_exist('svn_groups', 'include_aliase_count');

            //增加表 svn_second_pri
            $this->database->query("
        CREATE TABLE `svn_second_pri`  (
          `svn_second_pri_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `svnn_user_pri_path_id` int(10) UNSIGNED NOT NULL,
          `svn_object_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
          `svn_object_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
          PRIMARY KEY (`svn_second_pri_id`) USING BTREE
        ) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;");
            $this->check_table_exist('svn_second_pri');

            //表 svn_user_pri_paths 增加字段 second_pri
            $this->database->query("ALTER TABLE `svn_user_pri_paths` ADD COLUMN `second_pri` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否可二次授权' AFTER `unique`;");
            $this->check_column_exist('svn_user_pri_paths', 'second_pri');

            //表 svn_users 增加字段 svn_user_last_login svn_user_token svn_user_mail
            $this->database->query("ALTER TABLE `svn_users` ADD COLUMN `svn_user_last_login` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '上次登录时间' AFTER `svn_user_note`;");
            $this->database->query("ALTER TABLE `svn_users` ADD COLUMN `svn_user_token` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户token' AFTER `svn_user_last_login`;");
            $this->database->query("ALTER TABLE `svn_users` ADD COLUMN `svn_user_mail` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户token' AFTER `svn_user_token`;");
            $this->check_column_exist('svn_users', 'svn_user_last_login', 'svn_user_token', 'svn_user_mail');
        } else {
            //表 admin_users 增加字段 admin_user_token
            $this->database->query("ALTER TABLE `admin_users` ADD COLUMN `admin_user_token` TEXT;");
            $this->check_column_exist('admin_users', 'admin_user_token');

            //增加表 tasks
            $this->database->query('CREATE TABLE "tasks" (
                "task_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                "task_name" TEXT NOT NULL,
                "task_status" integer NOT NULL,
                "task_cmd" TEXT NOT NULL,
                "task_type" TEXT NOT NULL,
                "task_unique" TEXT NOT NULL,
                "task_log_file" TEXT,
                "task_optional" TEXT,
                "task_create_time" TEXT NOT NULL,
                "task_update_time" TEXT
              );');
            $this->check_table_exist('tasks');

            //增加表 crond
            $this->database->query('CREATE TABLE "crond" (
                "crond_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                "sign" TEXT NOT NULL,
                "task_type" integer NOT NULL,
                "task_name" TEXT NOT NULL,
                "cycle_type" TEXT NOT NULL,
                "cycle_desc" TEXT NOT NULL,
                "status" integer NOT NULL,
                "save_count" integer NOT NULL,
                "rep_name" TEXT,
                "week" integer,
                "day" integer,
                "hour" integer,
                "minute" integer,
                "notice" integer NOT NULL,
                "code" TEXT NOT NULL,
                "shell" TEXT,
                "last_exec_time" TEXT NOT NULL,
                "create_time" TEXT NOT NULL
              );');
            $this->check_table_exist('crond');

            //增加表 subadmin
            $this->database->query('CREATE TABLE "subadmin" (
                "subadmin_id" INTEGER NOT NULL,
                "subadmin_name" TEXT NOT NULL,
                "subadmin_password" TEXT NOT NULL,
                "subadmin_phone" TEXT,
                "subadmin_email" TEXT,
                "subadmin_status" integer NOT NULL,
                "subadmin_note" TEXT,
                "subadmin_last_login" TEXT,
                "subadmin_create_time" TEXT NOT NULL,
                "subadmin_tree" TEXT,
                "subadmin_functions" TEXT,
                "subadmin_token" TEXT,
                PRIMARY KEY ("subadmin_id")
              );');
            $this->check_table_exist('subadmin');

            //表 svn_groups 增加字段 include_aliase_count
            $this->database->query("ALTER TABLE `svn_groups` ADD COLUMN `include_aliase_count` INTEGER;");
            $this->check_column_exist('svn_groups', 'include_aliase_count');

            //增加表 svn_second_pri
            $this->database->query('CREATE TABLE "svn_second_pri" (
                "svn_second_pri_id" INTEGER NOT NULL,
                "svnn_user_pri_path_id" INTEGER NOT NULL,
                "svn_object_type" TEXT NOT NULL,
                "svn_object_name" TEXT NOT NULL,
                PRIMARY KEY ("svn_second_pri_id")
              );');
            $this->check_table_exist('svn_second_pri');

            //表 svn_user_pri_paths 增加字段 second_pri
            $this->database->query("ALTER TABLE `svn_user_pri_paths` ADD COLUMN `second_pri` INTEGER;");
            $this->check_column_exist('svn_user_pri_paths', 'second_pri');

            //表 svn_users 增加字段 svn_user_last_login svn_user_token svn_user_mail
            $this->database->query("ALTER TABLE `svn_users` ADD COLUMN `svn_user_last_login` TEXT");
            $this->database->query("ALTER TABLE `svn_users` ADD COLUMN `svn_user_token` TEXT");
            $this->database->query("ALTER TABLE `svn_users` ADD COLUMN `svn_user_mail` TEXT");
            $this->check_column_exist('svn_users', 'svn_user_last_login', 'svn_user_token', 'svn_user_mail');
        }
    }

    //升级配置文件 包括读取原有配置文件写入新的配置 包括创建新的配置文件 todo
    private function update_config()
    {
        sleep(1);

        //version
        $version = Config::get('version');
        $oldVersion = $version['version'];

        //daemon SOCKET_READ_LENGTH
        $configDaemon = Config::get('daemon');
        $daemon = sprintf(
            file_get_contents(BASE_PATH . '/server/update/templete/daemon'),
            isset($configDaemon['SOCKET_READ_LENGTH']) ? $configDaemon['SOCKET_READ_LENGTH'] : $configDaemon['socket_data_length'],
            isset($configDaemon['SOCKET_LISTEN_BACKLOG']) ? $configDaemon['SOCKET_LISTEN_BACKLOG'] : $configDaemon['socket_listen_backlog']
        );

        //bin svn svnadmin svnlook svnserve svnversion svnsync svnrdump svndumpfilter svnmucc svnauthz-validate
        $configBin = Config::get('bin');
        $bin = sprintf(
            file_get_contents(BASE_PATH . '/server/update/templete/bin'),
            $configBin['svn'],
            $configBin['svnadmin'],
            $configBin['svnlook'],
            $configBin['svnserve'],
            $configBin['svnversion'],
            $configBin['svnsync'],
            $configBin['svnrdump'],
            $configBin['svndumpfilter'],
            $configBin['svnmucc'],
            isset($configBin['svnauthz-validate']) ? $configBin['svnauthz-validate'] : '',
            isset($configBin['saslauthd']) ? $configBin['saslauthd'] : '',
            isset($configBin['httpd']) ? $configBin['httpd'] : '',
            isset($configBin['htpasswd']) ? $configBin['htpasswd'] : ''
        );

        //database file_get_contents
        $database = file_get_contents(BASE_PATH . '/config/database.php');

        //svn home_path
        $configSvn = Config::get('svn');
        $svn = sprintf(
            file_get_contents(BASE_PATH . '/server/update/templete/svn'),
            $configSvn['home_path']
        );

        //覆盖代码
        passthru("alias cp='cp'");
        passthru(sprintf("cp -r -f '%s'/* '%s'", BASE_PATH . '/server/update/code', BASE_PATH . '/'));

        //重新写入
        file_put_contents(BASE_PATH . '/config/daemon.php', $daemon);
        file_put_contents(BASE_PATH . '/config/bin.php', $bin);
        file_put_contents(BASE_PATH . '/config/database.php', $database);
        file_put_contents(BASE_PATH . '/config/svn.php', $svn);

        //检查是否写入成功 todo
        $version = Config::get('version');
        $newVersion = $version['version'];
        if ($oldVersion == $newVersion) {
            echo sprintf('更新失败-版本号错误%s', PHP_EOL);
            exit;
        }

        //创建必要文件

        //创建sasl目录
        $configSvn = Config::get('svn');

        is_dir($configSvn['sasl_home']) ? '' : mkdir($configSvn['sasl_home'], 0754, true);

        //创建ldap目录
        is_dir($configSvn['ldap_home']) ? '' : mkdir($configSvn['ldap_home'], 0754, true);

        //创建crond目录
        is_dir($configSvn['crond_base_path']) ? '' : mkdir($configSvn['crond_base_path'], 0754, true);

        //ldap服务器配置文件
        file_put_contents($configSvn['ldap_config_file'], '');

        //写入httpPasswd文件
        if (!file_exists($configSvn['http_passwd_file'])) {
            file_put_contents($configSvn['http_passwd_file'], '');
        }

        //提醒授权
        passthru(sprintf("chown -R apache:apache '%s'", $configSvn['home_path']));

        //安装sasl依赖提醒 安装 mod_dav_svn 提醒

        echo sprintf('配置文件更新成功%s', PHP_EOL);
    }

    //禁用函数检查
    private function check_disable_function()
    {
        sleep(1);

        $disable_functions = explode(',', ini_get('disable_functions'));
        foreach ($disable_functions as $disable) {
            if (in_array(trim($disable), $this->require_functions)) {
                print_r(sprintf('需要的函数[%s]被禁用%s', $disable, PHP_EOL));
                exit;
            }
        }
    }

    //配置文件检查
    private function check_config()
    {
        sleep(1);

        foreach ($this->config_files as $config_file) {
            if (!file_exists(BASE_PATH . '/config/' . $config_file)) {
                echo sprintf('文件[%s]不存在-请检查当前升级包路径是否在[server]目录下%s', BASE_PATH . '/config/' . $config_file, PHP_EOL);
                exit;
            }
        }
    }

    //表存在确认
    private function check_table_exist($table)
    {
        if ($this->database_type == 'mysql') {
            $result = $this->database->query('show tables')->fetchAll();
            $result = array_column(empty($result) ? [] : $result, 0);
            if (!in_array($table, $result)) {
                echo sprintf('表[%s]创建失败-请检查数据库%s', $table, PHP_EOL);
                exit;
            }
        } else {
            $result = $this->database->query(sprintf('SELECT count(*) as count FROM sqlite_master WHERE type="table" AND name = "%s";', $table))->fetchAll();
            if (!isset($result[0]['count'])) {
                echo sprintf('表[%s]创建失败-请检查数据库%s', $table, PHP_EOL);
                exit;
            }
        }

        echo sprintf('数据库更新成功%s', PHP_EOL);
    }

    //字段存在确认
    private function check_column_exist($table, ...$columns)
    {
        if (empty($columns)) {
            echo sprintf('无可判断字段%s', PHP_EOL);
            exit;
        }

        if ($this->database_type == 'mysql') {
            $result = $this->database->query(sprintf('show columns from %s', $table))->fetchAll();
            $result = array_column(empty($result) ? [] : $result, 'Field');
            if (empty($result)) {
                echo sprintf('表[%s]无字段-请检查数据库%s', $table, PHP_EOL);
                exit;
            }
            foreach ($columns as $column) {
                if (!in_array($column, $result)) {
                    echo sprintf('表[%s]新增字段[%s]失败-请检查数据库%s', $table, $column, PHP_EOL);
                    exit;
                }

                echo sprintf('数据库更新成功%s', PHP_EOL);
            }
        } else {
            $result = $this->database->query(sprintf('PRAGMA table_info(%s)', $table))->fetchAll();
            $result = array_column(empty($result) ? [] : $result, 'name');
            if (empty($result)) {
                echo sprintf('表[%s]无字段-请检查数据库%s', $table, PHP_EOL);
                exit;
            }
            foreach ($columns as $column) {
                if (!in_array($column, $result)) {
                    echo sprintf('表[%s]新增字段[%s]失败-请检查数据库%s', $table, $column, PHP_EOL);
                    exit;
                }

                echo sprintf('数据库更新成功%s', PHP_EOL);
            }
        }
    }

    //安装提醒
    private function warning_extension()
    {
        $extensions = [
            'php-mysql',
            'php-pdo',
            'php-bcmath',
            'php-ldap',
            'php-mbstring',

            'mod_dav_svn',
            'mod_ldap'
        ];
        $warning = sprintf(
            '%s===============================================%s%s如果您当前为源码部署而非容器部署%s%s需要升级程序执行结束后自行安装以下php依赖和第三方依赖！！！%s%s[ %s ]%s%s===============================================%s%s',
            "\n",
            "\n",
            "\n",
            "\n",
            "\n",
            "\n",
            "\n",
            implode(' | ', $extensions),
            "\n",
            "\n",
            "\n",
            "\n"
        );

        echo $warning;
    }
}

class Config
{
    /**
     * 配置文件目录
     *
     * @var string
     */
    public static $_configPath = '';

    /**
     * 自动include
     *
     * @param string $configPath
     * @return void
     */
    public static function load($configPath)
    {
        self::$_configPath = $configPath;
    }

    /**
     * 获取配置信息value
     *
     * @param string $section
     * @param array $default
     * @return array
     */
    public static function get($section = null, $default = [])
    {
        if (is_file(self::$_configPath . $section . '.php')) {
            $config = include self::$_configPath . $section . '.php';
            return $config;
        }
        return $default;
    }
}

(new Update())->update();
