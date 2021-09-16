<?php

/*
 * 与svn服务相关的方法的封装
 * 
 * 相关目录：
 * 1、conf目录地址：/www/svn/conf
 * 2、repository地址：/www/svn/repository
 * 3、/etc/sysconfig/svnserve 文件内容指定了SVN服务的repository目录 不清楚为自动生成的配置或为手动生成的配置
 * 4、/etc/subversion 
 * 5、SVN项目部署目录：自定义 文件会自动保存在自定义的部署目录
 * 6、/var/svn 为subversive的默认仓库目录
 * 7、如果在本地检出时，http://domain/仓库名称/format 无法访问 权限不够 需要关闭Linux系统的selinux
 */

class Svnserve extends Controller {
    /*
     * 注意事项：
     * 1、所有的控制器都要继承基类控制器：Controller
     * 2、基类控制器中包含：数据库连接对象、守护进程通信对象、视图层对象、公共函数等，继承后可以直接使用基类的变量和对象
     * 
     * 用法：
     * 1、使用父类的变量：$this->xxx
     * 2、使用父类的成员函数：parent::yyy()
     * 3、使用父类的非成员函数，直接用即可：zzz() 
     * 4、
     */

    private $Config;
    private $svn_repository_path;
    private $server_domain;
    private $protocol;
    private $svn_web_path;
    private $svn_port;
    private $http_port;
    private $server_ip;
    private $Firewall;
    private $System;
    private $Mail;
    private $Mod_dav_svn_status;
    private $Clientinfo;

    function __construct() {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
        $this->Config = new Config();

        $this->Firewall = new Firewall();

        $this->System = new System();

        $this->Mail = new Mail();

        $this->Clientinfo = new Clientinfo();

        $this->svn_repository_path = $this->Config->Get("SVN_REPOSITORY_PATH");
        $this->server_domain = $this->Config->Get("SERVER_DOMAIN");
        $this->server_ip = $this->Config->Get("SERVER_IP");
        $this->protocol = $this->Config->Get("PROTOCOL");
        $this->svn_web_path = $this->Config->Get("SVN_WEB_PATH");
        $this->svn_port = $this->Config->Get("SVN_PORT");
        $this->http_port = $this->Config->Get("HTTP_PORT");
        $this->Mod_dav_svn_status = $this->Config->Get("Mod_dav_svn_status");
    }

    //设置仓库的hooks
    function SetRepositoryHooks($requestPayload) {
        $repository_name = trim($requestPayload['repository_name']);
        $hooks_type_list = $requestPayload['hooks_type_list'];

        if (!is_dir($this->svn_repository_path . '/' . $repository_name . '/' . 'hooks')) {
            $data['status'] = 0;
            $data['message'] = '仓库不存在或文件损坏';
            return $data;
        }
        parent::RequestReplyExec(' chmod 777 -R ' . $this->svn_repository_path);
        foreach ($hooks_type_list as $key => $value) {
            file_put_contents($this->svn_repository_path . '/' . $repository_name . '/' . 'hooks' . '/' . $value['value'], $value["shell"]);
        }
        $data['status'] = 1;
        $data['message'] = '设置hooks数据成功';
        return $data;
    }

    //获取仓库的hooks
    function GetRepositoryHooks($requestPayload) {
        $repository_name = trim($requestPayload['repository_name']);

        if (empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }
        if (!is_dir($this->svn_repository_path . '/' . $repository_name . '/' . 'hooks')) {
            $data['status'] = 0;
            $data['message'] = '仓库不存在或文件损坏';
            return $data;
        }
        $hooks_type_list = array(
            "start-commit" => array(
                "value" => "start-commit",
                "label" => "start-commit---事务创建前",
                "shell" => ""
            ),
            "pre-commit" => array(
                "value" => "pre-commit",
                "label" => "pre-commit---事务提交前",
                "shell" => ""
            ),
            "post-commit" => array(
                "value" => "post-commit",
                "label" => "post-commit---事务提交后",
                "shell" => ""
            ),
            "pre-lock" => array(
                "value" => "pre-lock",
                "label" => "pre-lock---锁定文件前",
                "shell" => ""
            ),
            "post-lock" => array(
                "value" => "post-lock",
                "label" => "post-lock---锁定文件后",
                "shell" => ""
            ),
            "pre-unlock" => array(
                "value" => "pre-unlock",
                "label" => "pre-unlock---解锁文件前",
                "shell" => ""
            ),
            "post-unlock" => array(
                "value" => "post-unlock",
                "label" => "post-unlock---解锁文件后",
                "shell" => ""
            ),
            "pre-revprop-change" => array(
                "value" => "pre-revprop-change",
                "label" => "pre-revprop-change---修改修订版属性前",
                "shell" => ""
            ),
            "post-revprop-change" => array(
                "value" => "post-revprop-change",
                "label" => "post-revprop-change---修改修订版属性后",
                "shell" => ""
            ),
        );
        $hooks_file_list = array(
            "start-commit",
            "pre-commit",
            "post-commit",
            "pre-lock",
            "post-lock",
            "pre-unlock",
            "post-unlock",
            "pre-revprop-change",
            "post-revprop-change"
        );
        $file_arr = scandir($this->svn_repository_path . '/' . $repository_name . '/' . 'hooks');
        foreach ($file_arr as $file_item) {
            if ($file_item != '.' && $file_item != '..') {
                if (in_array($file_item, $hooks_file_list)) {
                    $hooks_type_list[$file_item]['shell'] = file_get_contents($this->svn_repository_path . '/' . $repository_name . '/' . 'hooks' . '/' . $file_item);
                }
            }
        }
        $data['status'] = 1;
        $data['data'] = $hooks_type_list;
        $data['message'] = '获取hooks数据成功';
        return $data;
    }

    //系统首页 获取概览情况
    function GetGailan($requestPayload) {
        $userid = $this->this_userid;

        $resultlist = array(
            'os_type' => "", //操作系统类型
            'os_runtime' => "", //系统运行天数
            'repository_count' => "", //svn仓库数量
            'admin_count' => "", //系统管理员数量
            'user_count' => "", //普通用户数量
        );

        //操作系统类型
        $resultlist['os_type'] = file('/etc/os-release');
        if (file_exists('/etc/redhat-release')) {
            $resultlist['os_type'] = "CentOS";
        } elseif (file_exists('etc/lsb-release')) {
            $resultlist['os_type'] = "Ubuntu";
        } else {
            $resultlist['os_type'] = "-";
        }
        //服务器运行天数
        $info = trim(explode(" ", file_get_contents('/proc/uptime'))[0]); //系统自启动开始的秒数
        $resultlist['os_runtime'] = floor($info / 60 / 60 / 24);
        //svn仓库数量
        $svn_check_status = $this->CheckSvnserveStatus();
        $resultlist['repository_count'] = 0;
        if ($svn_check_status['code'] == '01' || $svn_check_status['code'] == '11') {
            $i = 0;
            $file_arr = scandir($this->svn_repository_path);
            foreach ($file_arr as $file_item) {
                if ($file_item != '.' && $file_item != '..') {
                    if (is_dir($this->svn_repository_path . '/' . $file_item)) {
                        $file_arr2 = scandir($this->svn_repository_path . '/' . $file_item);
                        foreach ($file_arr2 as $file_item2) {
                            if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                                $i++;
                                break;
                            }
                        }
                    }
                }
            }
            $resultlist['repository_count'] = $i;
        }
        //超级管理员数量
        $resultlist['super_count'] = $this->database_medoo->count("user", ["roleid" => '1']);
        //系统管理员数量
        $resultlist['sys_count'] = $this->database_medoo->count("user", ["roleid" => '2']);
        //普通用户数量
        $resultlist['user_count'] = $this->database_medoo->count("user", ["roleid" => '3']);

        $data['status'] = 1;
        $data['data'] = $resultlist;
        $data['message'] = '获取概览数据成功';
        return $data;
    }

    //安装svnserve服务
    function Install($requestPayload) {
        $platform = $this->System->GetPlatform();

        $data = array();

        if (!$platform['status'] == 1) {
            return $platform;
        }
        $platform = $platform['platform'];

        //创建svn仓库父目录
        parent::RequestReplyExec(' mkdir -p ' . $this->svn_repository_path);
        if (!is_dir($this->svn_repository_path)) {
            $data['status'] = 0;
            $data['message'] = '安装失败 创建目录失败';
            return $data;
        }

        //通过ps auxf|grep -v "grep"|grep svnserve和判断文件/usr/bin/svnserve是否存在这两方面来同时判断 如果没有安装过则进行安装
        $info = parent::RequestReplyExec(' ps auxf|grep -v "grep"|grep svnserve');
        if ($info == '' && !file_exists('/usr/bin/svnserve')) {
            if ($platform == 'CentOS') {
                parent::RequestReplyExec(" yum install -y subversion");
                /*
                 * 安装目录浏览的依赖模块
                 * 在/etc/httpd/conf.d/下创建空文件AuthUserFile.conf
                 */
                if ($this->Mod_dav_svn_status) {
                    parent::RequestReplyExec(" yum install -y mod_dav_svn");
                    parent::RequestReplyExec(" touch /etc/httpd/conf.d/AuthUserFile.conf");
                    parent::RequestReplyExec(" chmod 777 /etc/httpd/conf.d/AuthUserFile.conf");
                }

                //通常cp的别名为cp -i ，取消别名
                parent::RequestReplyExec(" alias cp='cp'");
                parent::RequestReplyExec(' cp -f /etc/sysconfig/svnserve /etc/sysconfig/svnserve.bak');
                //更改存储库位置 将配置文件/etc/sysconfig/svnserve中的/var/svn/更换为svn仓库目录
                parent::RequestReplyExec(' sed -i \'s/\/var\/svn/' . str_replace('/', '\/', $this->svn_repository_path) . '/g\'' . ' /etc/sysconfig/svnserve');
                //设置存储密码选项 将以下内容写入文件/etc/subversion/servers servers文件不存在则创建
                /*
                 * [groups]
                 * [global]
                 * store-plaintext-passwords = yes
                 */
                parent::RequestReplyExec(" touch /etc/subversion/servers");
                $con = "[groups]\n[global]\nstore-plaintext-passwords = yes\n";
                parent::RequestReplyExec(' echo \'' . $con . '\' > /etc/subversion/servers');
                parent::RequestReplyExec(" systemctl reload httpd");
            } else if ($platform == 'Ubuntu') {
                parent::RequestReplyExec(' apt install subversion -y');
                parent::RequestReplyExec(' cp /etc/subversion/servers /etc/subversion/servers.bak');
                //设置存储密码选项 将文件/etc/subversion/servers中store-plaintext-passwords对应的值由no改为yes
                parent::RequestReplyExec(" sed -i 's/# store-plaintext-passwords = no/store-plaintext-passwords = yes/g' /etc/subversion/servers");
                //将以下内容写入文件/lib/systemd/system/svnserve.service 
                /*
                 * [Unit]
                 * Description=Subversion protocol daemon
                 * After=syslog.target network.target
                 * 
                 * [Service]
                 * Type=forking
                 * ExecStart=/usr/bin/svnserve --daemon --pid-file=/run/svnserve.pid -r /www/svn/repository
                 * 
                 * [Install]
                 * WantedBy=multi-user.target
                 */
                if (!file_exists('/lib/systemd/system/svnserve.service')) {
                    $data['status'] = 0;
                    $data['message'] = '文件/lib/systemd/system/svnserve.service不存在';
                    return $data;
                }
                parent::RequestReplyExec(' cp /lib/systemd/system/svnserve.service /lib/systemd/system/svnserve.service.bak');
                $con = "\n[Unit]\nDescription=Subversion protocol daemon\nAfter=syslog.target network.target\n\n[Service]\nType=forking\nExecStart=/usr/bin/svnserve --daemon --pid-file=/run/svnserve.pid -r " . $this->svn_repository_path . "\n\n[Install]\nWantedBy=multi-user.target\n";
                parent::RequestReplyExec(" echo '$con' >> /lib/systemd/system/svnserve.service");
                //执行命令systemctl daemon-reload使配置生效
                parent::RequestReplyExec(' systemctl daemon-reload');
            } else {
                $data['status'] = 0;
                $data['message'] = '当前操作系统不受支持';
                return $data;
            }
            parent::RequestReplyExec(" systemctl enable svnserve.service");
            parent::RequestReplyExec(" systemctl start svnserve.service");
            $this->Firewall->SetFirewallPolicy(["port"=>$this->svn_port,"type"=>"add"]);
            $this->Firewall->SetFirewallPolicy(["port"=>$this->http_port,"type"=>"add"]);
            // $this->Firewall->SetFirewallPolicy('tcp', $this->svn_port, 'add');
            // $this->Firewall->SetFirewallPolicy('tcp', $this->http_port, 'add');
            parent::RequestReplyExec(' setenforce 0');
            parent::RequestReplyExec(" sed -i 's/SELINUX=enforcing/SELINUX=disabled/g' /etc/selinux/config");
        } else {
            if ($platform == 'CentOS') {
                $this->UnInstall(array());
                $this->Install(array());
            } else if ($platform == 'Ubuntu') {
                
            }
        }

        $data['status'] = 1;
        $data['message'] = '安装服务成功';
        return $data;
    }

    //卸载svnserve服务
    function UnInstall($requestPayload) {
        //清空表数据
        $this->TruncateTable();

        $platform = $this->System->GetPlatform();

        parent::RequestReplyExec(" rm -rf " . $this->svn_repository_path);
        if (is_dir($this->svn_repository_path)) {
            $data['status'] = 0;
            $data['message'] = '卸载失败 删除目录失败';
            return $data;
        }
        //判断平台
        if (!$platform['status'] == 1) {
            return $platform;
        }
        $platform = $platform['platform'];

        parent::RequestReplyExec(' systemctl stop svnserve');
        parent::RequestReplyExec(' systemctl disable svnserve');
        parent::RequestReplyExec(' yum remove -y subversion');
        /*
         * 目录浏览
         * 卸载开启目录浏览的支持模块
         * 删除配置文件
         */
        if ($this->Mod_dav_svn_status) {
            parent::RequestReplyExec(' yum remove -y mod_dav_svn');
            parent::RequestReplyExec(" rm -f /etc/httpd/conf.d/AuthUserFile.conf");
        }

        if ($platform == 'CentOS') {
            parent::RequestReplyExec(' rm -f /etc/subversion/servers');
            parent::RequestReplyExec(' rm -rf /etc/subversion');
            //清除yum缓存
            parent::RequestReplyExec(' yum clean all');
        } else if ($platform == 'Ubuntu') {
            parent::RequestReplyExec(' mv -f /etc/subversion/servers.bak /etc/subversion/servers');
            parent::RequestReplyExec(' mv -f /lib/systemd/system/svnserve.service.bak /lib/systemd/system/svnserve.service');
            parent::RequestReplyExec(' systemctl daemon-reload');
            parent::RequestReplyExec('  apt-get clean');
        }

        //is_dir的结果会被缓存，所以需要清除缓存
        clearstatcache();

        $data['status'] = 1;
        $data['message'] = '卸载服务成功';
        return $data;
    }

    //修复svn服务 包括重新扫描仓库列表重新写入仓库表
    function Repaire($requestPayload) {
        //清空仓库表和用户-仓库表
        $this->TruncateTable();
        //扫描仓库并写入仓库表
        $this->ScanRepository();
        //根据仓库表完善信息
        $this->UpdateRepositoryInfo();

        $data['status'] = 1;
        $data['message'] = '修复成功';
        return $data;
    }

    //获取所有仓库信息 计划任务列表下拉菜单使用
    function GetAllRepositoryList($requestPayload) {
        $userid = $this->this_userid;

        //更新仓库表
        $this->UpdateRepositoryInfo();
        //根据用户角色筛选结果
        $result = $this->database_medoo->select("user", ["roleid"], ["id" => $userid]);
        if (empty($result)) {
            $data['status'] = 0;
            $data['message'] = '获取仓库列表失败 用户角色不在允许范围'; //检测到非法用户应该返回另外的状态码使token立即失效并退出登录
            $data['code'] = 401;
            return $data;
        }
        $list = array();
        if ($result[0]['roleid'] == "1" || $result[0]['roleid'] == "2") {
            //获取列表
            $list = $this->database_medoo->select("repository", [
                "repository_name(value)",
                    ], [
                "ORDER" => ["repository_edittime" => "DESC"],
            ]);
        } else {
            //用户的所有仓库列表
            $list = $this->database_medoo->select('user_repository', [
                "[>]repository" => [
                    "repositoryid" => "id"
                ],
                    ], [
                "repository.repository_name(value)",
                    ], [
                "userid" => $userid,
                "ORDER" => ["repository.repository_edittime" => "DESC"],
            ]);
        }
        //处理数据结构
        foreach ($list as $key => $value) {
            $list[$key]['label'] = $value['value'];
        }
        $data['status'] = 1;
        $data['message'] = '获取仓库列表成功';
        $data['data'] = $list;
        return $data;
    }

    //项目管理 获取仓库信息
    function GetRepositoryList($requestPayload) {
        $userid = $this->this_userid;
        $pageSize = trim($requestPayload['pageSize']);
        $currentPage = trim($requestPayload['currentPage']);

        //检查svn状态
        $svn_check_status = $this->CheckSvnserveStatus();
        if ($svn_check_status['status'] == 0) {
            $data['status'] = 0;
            $data['message'] = '获取仓库列表失败 ' . $svn_check_status['message'];
            return $data;
        }

        //检查参数
        if (empty(trim($pageSize)) || empty(trim($currentPage)) || trim($pageSize) == 0) {
            $data['status'] = 0;
            $data['message'] = '获取仓库列表失败 参数不完整或错误';
            return $data;
        }

        //更新仓库表
        $this->UpdateRepositoryInfo();

        //根据用户角色筛选结果
        $result = $this->database_medoo->select("user", ["roleid"], ["id" => $userid]);
        if (empty($result)) {
            $data['status'] = 0;
            $data['message'] = '获取仓库列表失败 用户角色不在允许范围'; //检测到非法用户应该返回另外的状态码使token立即失效并退出登录
            $data['code'] = 401;
            return $data;
        }
        if ($result[0]['roleid'] == "1" || $result[0]['roleid'] == "2") {
            //分页处理
            $begin = $pageSize * ($currentPage - 1);

            //获取列表
            $list = $this->database_medoo->select("repository", [
                "id",
                "repository_name",
                "repository_url",
                "repository_checkout_url",
                "repository_web_url",
                "repository_size",
                "repository_edittime"
                    ], [
                "ORDER" => ["repository_edittime" => "DESC"],
                "LIMIT" => [$begin, $pageSize]
            ]);

            //计算数量
            $total = $this->database_medoo->count("repository");

            //处理自增的id
            $i = 0;
            foreach ($list as $key => $value) {
                $list[$key]["id"] = $i + $begin;
                $i++;
            }

            $data['status'] = 1;
            $data['message'] = '获取仓库列表成功';
            $data['data'] = $list;
            $data['total'] = $total;
            return $data;
        } else {
            //分页处理
            $begin = $pageSize * ($currentPage - 1);

            //用户的所有仓库列表
            $list = $this->database_medoo->select('user_repository', [
                "[>]repository" => [
                    "repositoryid" => "id"
                ],
                    ], [
                "repository.id",
                "repository.repository_name",
                "repository.repository_url",
                "repository.repository_checkout_url",
                "repository.repository_web_url",
                "repository.repository_size",
                "repository.repository_edittime"
                    ], [
                "userid" => $userid,
                "ORDER" => ["repository.repository_edittime" => "DESC"],
                "LIMIT" => [$begin, $pageSize]
            ]);
            $total = $this->database_medoo->count('user_repository', [
                "userid" => $userid
            ]);

            //处理自增的id
            $i = 0;
            foreach ($list as $key => $value) {
                $list[$key]["id"] = $i + $begin;
                $i++;
            }
            $data['status'] = 1;
            $data['message'] = '获取仓库列表成功';
            $data['data'] = $list;
            $data['total'] = $total;
            return $data;
        }
    }

    //项目管理 按钮 添加svn仓库   包括项目标题
    function AddRepository($requestPayload) {
        $repository_name = $requestPayload['repository_name'];
        $this_userid = $this->this_userid;
        $this_username = $this->this_userid;

        $svn_check_status = $this->CheckSvnserveStatus();
        if ($svn_check_status['status'] == 0) {
            $data['status'] = 0;
            $data['message'] = '添加仓库失败 ' . $svn_check_status['message'];
            return $data;
        }
        //只允许数字 字母 中文 下划线
        if (!$this->CheckStr($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '添加仓库失败 包含非法字符';
            return $data;
        }
        //判断仓库是否存在
        if (is_dir($this->svn_repository_path . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '添加仓库失败 仓库已经存在';
            return $data;
        }
        //创建仓库
        //解决创建中文仓库乱码问题
        parent::RequestReplyExec('export LC_CTYPE=en_US.UTF-8 &&  svnadmin create ' . $this->svn_repository_path . '/' . $repository_name);
        //判断是否创建成功
        if (!is_dir($this->svn_repository_path . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '添加仓库失败';
            return $data;
        }
        parent::RequestReplyExec(' chmod 777 -R ' . $this->svn_repository_path);
        /*
         * 配置目录浏览部分
         * 1、在conf目录下创建用户目录浏览的用户文件http_passwd
         * 2、在/etc/httpd/conf.d/AuthUserFile.conf文件中检测是否包含本仓库记录 如果包含 则不进行写入
         */
        if ($this->Mod_dav_svn_status) {
            parent::RequestReplyExec(" touch " . $this->svn_repository_path . "/" . $repository_name . "/conf/http_passwd");
            parent::RequestReplyExec(' chmod 777 -R ' . $this->svn_repository_path);
            //读取文本到数组
            $file = fopen("/etc/httpd/conf.d/AuthUserFile.conf", "r") or exit("无法打开文件!");
            $file_content = array();
            while (!feof($file)) {
                array_push($file_content, fgets($file));
            }
            fclose($file);
            //判断不存在记录则写入
            $flag = true;
            foreach ($file_content as $key => $value) {
                if (strstr($value, "<Location " . $this->svn_web_path . "/" . $repository_name . ">")) {
                    $flag = false;
                    break;
                }
            }
            if ($flag) {
                $con = "<Location " . $this->svn_web_path . "/" . $repository_name . ">\n"
                        . "DAV svn\n"
                        . "SVNPath " . $this->svn_repository_path . "/" . $repository_name . "\n"
                        . "AuthType Basic\n"
                        . "AuthName \"Authorization SVN\"\n"
                        . "AuthzSVNAccessFile " . $this->svn_repository_path . "/" . $repository_name . "/conf/authz\n"
                        . "AuthUserFile " . $this->svn_repository_path . "/" . $repository_name . "/conf/http_passwd\n"
                        . "Require valid-user\n"
                        . "</Location>\n";
                parent::RequestReplyExec(" echo '$con' >> /etc/httpd/conf.d/AuthUserFile.conf");
            }
        }

        //将新建仓库目录下的conf/svnserve.conf做以下修改，
        /*
         * 取消注释# anon-access = read所在行
         * 取消注释# password-db = passwd所在行
         * 取消注释# authz-db = authz所在行
         */
        parent::RequestReplyExec(" sed -i 's/# anon-access = read/anon-access = none/g' " . $this->svn_repository_path . "/" . $repository_name . "/conf/svnserve.conf");
        parent::RequestReplyExec(" sed -i 's/# password-db = passwd/password-db = passwd/g' " . $this->svn_repository_path . "/" . $repository_name . "/conf/svnserve.conf");
        parent::RequestReplyExec(" sed -i 's/# authz-db = authz/authz-db = authz/g' " . $this->svn_repository_path . "/" . $repository_name . "/conf/svnserve.conf");
        $this->InitRepositoryConfFile($repository_name);
        /*
         * 配置目录浏览后重载服务·
         */
        if ($this->Mod_dav_svn_status) {
            parent::RequestReplyExec(" systemctl reload httpd");
        }
        parent::RequestReplyExec(' setenforce 0');

        if (!$this->InsertRepositoryTable($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '添加仓库成功 但写入仓库表失败';
            return $data;
        }

        //发送邮件
        $time = date("Y-m-d-H-i-s");
        $ip = $send_content = ""
                . "被创建的仓库名称：$repository_name \n"
                . "操作用户：$this_username \n"
                . "操作用户uid：$this_userid \n"
                . "服务器已设置域名：$this->server_domain \n"
                . "服务器已设置IP地址：$this->server_ip \n"
                . "当前时间：$time";
        $send_title = "SVN仓库创建通知";
        $receive_roleid = 2;
        $receive_userid = 1;
        $this->Mail->SendMail($send_title, $send_content, $receive_roleid, $receive_userid);

        $data['status'] = 1;
        $data['message'] = '添加仓库成功';
        return $data;
    }

    //项目管理 按钮 删除svn仓库
    function DeleteRepository($requestPayload) {
        $repository_name = $requestPayload['repository_name'];
        $this_userid = $this->this_userid;
        $this_username = $this->this_userid;

        if (empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        //删除仓库目录
        if (!is_dir($this->svn_repository_path . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '失败,项目不存在';
            return $data;
        }
        parent::RequestReplyExec(' rm -rf ' . $this->svn_repository_path . '/' . $repository_name);

        //检查是否删除成功
        if (!is_dir($this->svn_repository_path . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '删除仓库失败';
            return $data;
        }

        if (!$this->DeleteRepositoryTable($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '删除仓库成功但从表中删除仓库信息失败';
            return $data;
        }

        //发送邮件
        $time = date("Y-m-d-H-i-s");
        $send_content = ""
                . "被删除的仓库名称：$repository_name \n"
                . "操作用户：$this_username \n"
                . "操作用户uid：$this_userid \n"
                . "服务器已设置域名：$this->server_domain \n"
                . "服务器已设置IP地址：$this->server_ip \n"
                . "当前时间：$time";
        $send_title = "SVN仓库删除通知";
        $receive_roleid = 2;
        $receive_userid = 1;
        $this->Mail->SendMail($send_title, $send_content, $receive_roleid, $receive_userid);

        $data['status'] = 1;
        $data['message'] = '删除仓库成功';
        return $data;
    }

    //项目管理 按钮 为svn项目授权 -> 在账号列表收集并提交账户相对于svn项目的权限变化
    function SetRepositoryPrivilege($requestPayload) {
        $repository_name = trim($requestPayload['repository_name']);
        $this_account_list = $requestPayload['this_account_list'];

        if (empty($repository_name) || empty($this_account_list)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if (!is_dir($this->svn_repository_path . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '项目不存在';
            return $data;
        }

        //这里只从一维的角度判断两个数组键值是否一致，也可以进行拆分数组，深入判断数据的键值和内容是否符合要求
        $all_account_list = $this->GetRepositoryUserList(array("repository_name" => $repository_name))['data'];
        if (!empty(array_diff_key($all_account_list, $this_account_list))) {
            $data['status'] = 0;
            $data['message'] = '传递的数组参数不符合要求';
            return $data;
        }
        //读文件写入数组
        $file = fopen($this->svn_repository_path . '/' . $repository_name . '/conf/authz', "r") or exit("无法打开文件!");
        $file_content = array();
        while (!feof($file)) {
            array_push($file_content, fgets($file));
        }
        fclose($file);

        //判断处理
        for ($i = 0; $i < sizeof($file_content); $i++) {
            if (trim($file_content[$i]) == '[/]') {
                $file_content[$i] = '';
                for ($j = $i + 1; $j < sizeof($file_content); $j++) {
                    if (strstr($file_content[$j], '['))
                        break;
                    $file_content[$j] = '';
                }
            }
        }
        $con = implode($file_content);

        //读取数组 拼接项目与账户权限字符串
        $con .= '[/]' . "\n";
        foreach ($this_account_list as $key => $value) {
            if ($value['privilege'] == 'rw' || $value['privilege'] == 'r') {
                $con .= $value['account'] . ' = ' . $value['privilege'] . "\n";
            }
        }

        parent::RequestReplyExec(' echo \'' . $con . '\' > ' . $this->svn_repository_path . '/' . $repository_name . '/conf/authz');

        $data['status'] = 1;
        $data['message'] = '账户授权成功';
        return $data;
    }

    //项目管理 按钮 编辑svn项目 提交用户对svn项目的标题的修改
    function SetRepositoryInfo($requestPayload) {
        $old_repository_name = trim($requestPayload['old_repository_name']);
        $new_repository_name = trim($requestPayload['new_repository_name']);

        //输入包含空格判断
        if (strstr($new_repository_name, ' ')) {
            $data['status'] = 0;
            $data['message'] = '修改失败 输入不规范';
            return $data;
        }
        //不为空判断
        if (empty($old_repository_name) || empty($new_repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }
        //目录是否存在判断
        if (!is_dir($this->svn_repository_path . '/' . $old_repository_name)) {
            $data['status'] = 0;
            $data['message'] = '要修改的项目不存在';
            return $data;
        }
        //是否重复
        if (is_dir($this->svn_repository_path . '/' . $new_repository_name)) {
            $data['status'] = 0;
            $data['message'] = '项目名称冲突';
            return $data;
        }
        //修改仓库文件夹的目录
        parent::RequestReplyExec(' mv ' . $this->svn_repository_path . '/' . $old_repository_name . ' ' . $this->svn_repository_path . '/' . $new_repository_name);
//        //修改authz文件中的仓库名称
//        parent::RequestReplyExec('sed -i \'s/' . $old_repository_name . '/' . $new_repository_name . '/g\' ' . SVN_CONF_PATH . '/authz');

        if (!$this->UpdateRepositoryName($old_repository_name, $new_repository_name)) {
            $data['status'] = 0;
            $data['message'] = '修改仓库信息成功但仓库表信息修改失败';
            return $data;
        }

        /*
         * 目录浏览
         */
        if ($this->Mod_dav_svn_status) {
            //读取文本到数组
            $file = fopen("/etc/httpd/conf.d/AuthUserFile.conf", "r") or exit("无法打开文件!");
            $file_content = array();
            while (!feof($file)) {
                array_push($file_content, fgets($file));
            }
            fclose($file);
            //如果存在记录则修改
            foreach ($file_content as $key => $value) {
                if (strstr($value, "<Location " . $this->svn_web_path . "/" . $old_repository_name . ">")) {
                    $file_content[$key] = "<Location " . $this->svn_web_path . "/" . $new_repository_name . ">\n";
                    $file_content[$key + 1] = "DAV svn\n";
                    $file_content[$key + 2] = "SVNPath " . $this->svn_repository_path . "/" . $old_repository_name . "\n";
                    $file_content[$key + 3] = "AuthType Basic\n";
                    $file_content[$key + 4] = "AuthName \"Authorization SVN\"\n";
                    $file_content[$key + 5] = "AuthzSVNAccessFile " . $this->svn_repository_path . "/" . $old_repository_name . "/conf/authz\n";
                    $file_content[$key + 6] = "AuthUserFile " . $this->svn_repository_path . "/" . $old_repository_name . "/conf/http_passwd\n";
                    $file_content[$key + 7] = "Require valid-user\n";
                    $file_content[$key + 8] = "</Location>\n";
                    break;
                }
            }
            //写入
            $file_content = implode($file_content);
            parent::RequestReplyExec(" echo '$file_content' > /etc/httpd/conf.d/AuthUserFile.conf");
            parent::RequestReplyExec(" systemctl reload httpd");
        }

        $data['status'] = 1;
        $data['message'] = '修改仓库信息成功';
        return $data;
    }

    //获取仓库对应的用户和密码列表
    function GetRepositoryUserList($requestPayload) {
        $repository_name = $requestPayload['repository_name'];

        if (empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        $svn_check_status = $this->CheckSvnserveStatus();
        if ($svn_check_status['status'] == 0) {
            $data['status'] = 0;
            $data['message'] = '获取仓库对应的账户列表失败 ' . $svn_check_status['message'];
            return $data;
        }

        $file = fopen($this->svn_repository_path . '/' . $repository_name . '/conf/passwd', "r") or exit('无法打开文件');
        $file_content = array();
        while (!feof($file)) {
            array_push($file_content, fgets($file));
        }
        fclose($file);
        $account_info = array();
        for ($i = 0, $j = 0; $i < sizeof($file_content); $i++) {
            if (strstr($file_content[$i], '=')) {
                $temp = explode('=', $file_content[$i]);
                $account_info[$j]['id'] = $j;
                $account_info[$j]['account'] = trim($temp[0]);
//                if ($is_need_passwd == 1) {
                $account_info[$j]['password'] = trim($temp[1]);
//                }
                $j++;
            }
        }

        $data['status'] = 1;
        $data['message'] = '获取仓库对应的账户列表成功';
        $data['data'] = $account_info;
        return $data;
    }

    //获取仓库对应的账户的权限
    function GetRepositoryUserPrivilegeList($requestPayload) {
        $repository_name = trim($requestPayload['repository_name']);

        if (empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if (!is_dir($this->svn_repository_path . '/' . $repository_name)) {
            $data['status'] = 0;
            $data['message'] = '项目不存在';
            return $data;
        }

        //读取文本到数组 获取该项目对应的所有账号和权限
        $file = fopen($this->svn_repository_path . '/' . $repository_name . '/conf/authz', "r") or exit("无法打开文件!");
        $file_content = array();
        while (!feof($file)) {
            array_push($file_content, fgets($file));
        }
        fclose($file);

        //获取该仓库的所有账号
        $all_account_list = array();
        $all_account_list = $this->GetRepositoryUserList(array("repository_name" => $repository_name))['data'];

        //聚合 目的是为了防止密码文件和权限文件中的用户列表不一致导致获取的数据不准确
        $temp = array();
        foreach ($all_account_list as $key => $value) {
            $all_account_list[$key]['privilege'] = '';
        }
        for ($i = 0; $i < sizeof($file_content); $i++) {
            if (strstr($file_content[$i], '=')) {
                $temp = explode('=', $file_content[$i]);
                $temp[0] = trim($temp[0]);
                $temp[1] = trim($temp[1]);
                foreach ($all_account_list as $key => $value) {
                    if ($value['account'] == $temp[0]) {
                        $all_account_list[$key]['privilege'] = $temp[1];
                        break;
                    }
                }
            }
        }

        //对权限字段为空的 设置为no
        foreach ($all_account_list as $key => $value) {
            if ($value['privilege'] == '') {
                $all_account_list[$key]['privilege'] = 'no';
            }
        }

        $data['status'] = 1;
        $data['message'] = '获取仓库对应账户及权限成功';
        $data['data'] = $all_account_list;
        return $data;
    }

    //账号管理 添加账号
    function AddAccount($requestPayload) {
        $repository_name = trim($requestPayload['repository_name']);
        $account = trim($requestPayload['account']);
        $passwd = trim($requestPayload['password']);

        $svn_check_status = $this->CheckSvnserveStatus();
        if ($svn_check_status['status'] == 0) {
            $data['status'] = 0;
            $data['message'] = '添加账户失败 ' . $svn_check_status['message'];
            return $data;
        }

        /*
         * 校验
         */
        $account = trim($account);
        $passwd = trim($passwd);

        //判断输入中是否包含空格
        if (strstr($account, ' ') || strstr($passwd, ' ')) {
            $data['status'] = 0;
            $data['message'] = '添加账户失败 输入不规范';
            $data['data'] = '';
            return $data;
        }

        //长度校验
        if (empty($account) || empty($passwd) || strlen($passwd) < 8) {
            $data['status'] = 0;
            $data['message'] = '失败 账户为空或密码长度不符合要求';
            return $data;
        }

        //是否包含特殊字符校验 待完成
        //账户冲突校验
        $account_list = array();
        $temp = $this->GetRepositoryUserList(array("repository_name" => $repository_name))['data'];
//        $temp = $this->GetAccountList(0, 99, 99)['data'];
        foreach ($temp as $key => $value) {
            array_push($account_list, $value['account']);
        }
        if (in_array($account, $account_list)) {
            $data['status'] = 0;
            $data['message'] = '失败 账户已存在';
            return $data;
        }
        /*
         * 添加账户
         */
        //写入数组
        $file = fopen($this->svn_repository_path . '/' . $repository_name . '/conf/passwd', "r") or exit("无法打开文件!");
        $file_content = array();
        while (!feof($file)) {
            array_push($file_content, fgets($file));
        }
        fclose($file);
        //写入文件
        array_push($file_content, $account . ' = ' . $passwd . "\n");
        $file_content = implode($file_content);
        parent::RequestReplyExec(' echo \'' . $file_content . '\' > ' . $this->svn_repository_path . '/' . $repository_name . '/conf/passwd');

        /*
         * 目录浏览
         * 向仓库的conf文件下的用户文件中添加用户和密码
         */
        if ($this->Mod_dav_svn_status) {
            parent::RequestReplyExec(" htpasswd -m -b  " . $this->svn_repository_path . "/" . $repository_name . "/conf/http_passwd $account $passwd");
            parent::RequestReplyExec(" sytemctl reload httpd");
        }

        $data['status'] = 1;
        $data['message'] = '添加账户成功';
        return $data;
    }

    //账号管理 删除账号
    function DeleteAccount($requestPayload) {
        $repository_name = trim($requestPayload['repository_name']);
        $account = trim($requestPayload['account']);

        if (empty($account)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($account == 'root') {
            $data['status'] = 0;
            $data['message'] = 'root账户不可删除';
            return $data;
        }

        //删除passwd文件中的账号密码
        $file = fopen($this->svn_repository_path . '/' . $repository_name . '/conf/passwd', "r") or exit("无法打开文件!");
        $file_content = array();
        while (!feof($file)) {
            array_push($file_content, fgets($file));
        }
        fclose($file);
        for ($i = 0; $i < sizeof($file_content); $i++) {
//            if (!strstr(trim($file_content[$i]), '[users]')) {
            if (strstr(trim($file_content[$i]), '=')) {
                $temp = trim(substr($file_content[$i], 0, strrpos($file_content[$i], '=')));
                if ($temp == $account) {
                    $file_content[$i] = '';
                    break;
                }
            }
        }
        $con = implode($file_content);
        parent::RequestReplyExec(' echo \'' . $con . '\' > ' . $this->svn_repository_path . '/' . $repository_name . '/conf/passwd');

        //删除authz文件中的账号
        $file = fopen($this->svn_repository_path . '/' . $repository_name . '/conf/authz', "r") or exit("无法打开文件!");
        $file_content = array();
        while (!feof($file)) {
            array_push($file_content, fgets($file));
        }
        fclose($file);
        for ($i = 0; $i < sizeof($file_content); $i++) {
            if (strstr(trim($file_content[$i]), '=')) {
                $temp = trim(substr($file_content[$i], 0, strrpos($file_content[$i], '=')));
                if ($temp == $account) {
                    $file_content[$i] = '';
                    //break;
                }
            }
        }
        $con = implode($file_content);
        parent::RequestReplyExec(' echo \'' . $con . '\' > ' . $this->svn_repository_path . '/' . $repository_name . '/conf/authz');

        /*
         * 目录浏览
         */
        if ($this->Mod_dav_svn_status) {
            parent::RequestReplyExec(" htpasswd -D  " . $this->svn_repository_path . "/" . $repository_name . "/conf/http_passwd $account");
            parent::RequestReplyExec(" sytemctl reload httpd");
        }

        $data['status'] = 1;
        $data['message'] = '删除账户成功';
        return $data;
    }

    //账号管理 编辑账号 提交用户对账号信息的修改 账号作为唯一标识不能修改
    function SetCountInfo($requestPayload) {
        $repository_name = trim($requestPayload['repository_name']);
        $account = trim($requestPayload['account']);
        $passwd = trim($requestPayload['password']);

        if (empty($account) || empty($passwd) || empty($repository_name)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        //判断输入中是否包含空格
        if (strstr($account, ' ') || strstr($passwd, ' ') || strstr($repository_name, ' ')) {
            $data['status'] = 0;
            $data['message'] = '修改密码失败 输入不规范';
            $data['data'] = '';
            return $data;
        }

        //修改passwd文件中的账号密码
        $file = fopen($this->svn_repository_path . '/' . $repository_name . '/conf/passwd', "r") or exit("无法打开文件!");
        $file_content = array();
        while (!feof($file)) {
            array_push($file_content, fgets($file));
        }
        fclose($file);
        for ($i = 0; $i < sizeof($file_content); $i++) {
//            if (!strstr(trim($file_content[$i]), '[users]')) {
            if (strstr(trim($file_content[$i]), '=')) {
                $temp = trim(substr($file_content[$i], 0, strrpos($file_content[$i], '=')));
                if ($temp == $account) {
                    $file_content[$i] = "\n" . $account . ' = ' . $passwd . "\n";
                    break;
                }
            }
        }
        $con = implode($file_content);
        parent::RequestReplyExec(' echo \'' . $con . '\' > ' . $this->svn_repository_path . '/' . $repository_name . '/conf/passwd');
        /*
         * 目录浏览
         */
        if ($this->Mod_dav_svn_status) {
            parent::RequestReplyExec(" htpasswd -m -b  " . $this->svn_repository_path . "/" . $repository_name . "/conf/http_passwd $account $passwd");
            parent::RequestReplyExec(" sytemctl reload httpd");
        }

        $data['status'] = 1;
        $data['message'] = '修改成功';
        return $data;
    }

    //高级设置 初始化加载 列出svnserve服务的状态
    function GetSvnserveStatus($requestPayload) {
        //是否安装服务
        $info = parent::RequestReplyExec('ps auxf|grep -v "grep"|grep svnserve');
        if ($info == '' && !file_exists('/usr/bin/svnserve')) {
            $info = array();
            $info['status'] = '未安装'; //未安装
            $info['port'] = '3690';
            $info['type'] = 'error';

            $data['status'] = 1;
            $data['message'] = '获取SVN服务状态成功';
            $data['data'] = $info;
            return $data;
        }
        //是否存在repository目录
        if (!is_dir($this->svn_repository_path)) {
            $info = array();
            $info['status'] = '异常'; //存储库目录不存在
            $info['port'] = '3690';
            $info['type'] = 'error';

            $data['status'] = 1;
            $data['message'] = '获取SVN服务状态成功';
            $data['data'] = $info;
            return $data;
        }
        //是否启动
        $info = parent::RequestReplyExec(' ps auxf|grep -v "grep"|grep svnserve');
        if ($info == '' && file_exists('/usr/bin/svnserve')) {
            $info = array();
            $info['status'] = '已停止'; //svn服务未启动
            $info['port'] = '3690';
            $info['type'] = 'warning';

            $data['status'] = 1;
            $data['message'] = '获取SVN服务状态成功';
            $data['data'] = $info;
            return $data;
        }

        $info = array();
        $info['status'] = '运行中'; //svn服务未启动
        $info['port'] = '3690';
        $info['type'] = 'success';

        $data['status'] = 1;
        $data['message'] = '获取SVN服务状态成功';
        $data['data'] = $info;
        return $data;
    }

    //高级设置 管理svnserve服务的状态
    function SetSvnserveStatus($requestPayload) {
        $action = $requestPayload['action'];

        if (empty($action)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        switch ($action) {
            case 'startSvn':
                parent::RequestReplyExec(' systemctl start svnserve');
                break;
            case 'restartSvn':
                parent::RequestReplyExec(' systemctl restart svnserve');
                break;
            case 'stopSvn':
                parent::RequestReplyExec(' systemctl stop svnserve');
                break;
        }

        $data['status'] = 1;
        $data['message'] = '设置SVN服务状态成功';
        return $data;
    }

    //获取随机的root密码
    private function GetInitPasswd($length) {
        !empty($length) or die('参数不完整');

        $str = md5(time());
        $token = substr($str, 5, $length);
        return $token;
    }

    //只允许中文 数字 字母
    private function CheckStr($str) {
        $res = preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u', $str);
        return $res ? true : false;
    }

    //获取服务状态 检查相关的目录文件是否存在
    private function CheckSvnserveStatus() {
        //是否安装服务
        $info = parent::RequestReplyExec(' ps auxf|grep -v "grep"|grep svnserve');
        if ($info == '' && !file_exists('/usr/bin/svnserve')) {
            $data['status'] = 0;
            $data['code'] = '00';
            $data['message'] = 'svn服务未安装';
            return $data;
        }
        //是否存在repository目录
        if (!is_dir($this->svn_repository_path)) {
            $data['status'] = 0;
            $data['code'] = '00';
            $data['message'] = '存储库目录不存在';
            return $data;
        }
        //是否启动
        $info = parent::RequestReplyExec(' ps auxf|grep -v "grep"|grep svnserve');
        if ($info == '' && file_exists('/usr/bin/svnserve')) {
            $data['status'] = 0;
            $data['code'] = '01';
            $data['message'] = 'svn服务未启动';
            return $data;
        }
        $data['status'] = 1;
        $data['code'] = '11';
        return $data;
    }

    //获取文件夹体积
    private function GetDirSize($dir) {
        clearstatcache();
        $dh = opendir($dir) or exit('打开目录错误'); //打开目录，返回一个目录流
        $size = 0;      //初始大小为0 
        while (false !== ($file = @readdir($dh))) {//循环读取目录下的文件
            if ($file != '.' and $file != '..') {
                $path = $dir . '/' . $file; //设置目录，用于含有子目录的情况
                if (is_dir($path)) {
                    $size += $this->GetDirSize($path); //递归调用，计算目录大小
                } elseif (is_file($path)) {
                    $size += filesize($path); //计算文件大小
                }
            }
        }
        closedir($dh); //关闭目录流
        return $size; //返回大小
    }

    //从仓库表中删除仓库信息
    private function DeleteRepositoryTable($repository_name) {
        $repository_name = trim($repository_name);
        if ($repository_name == "" || $repository_name == null) {
            return false;
        }
        //从仓库表中删除
        $result = $this->database_medoo->select("repository", ["id"], ["repository_name" => $repository_name]);
        if (empty($result)) {
            return false;
        }
        $repositoryid = $result[0]['id']; //仓库id
        $result = $this->database_medoo->delete("repository", [
            "AND" => [
                "repository_name" => $repository_name
            ]
        ]);
        if (!$result->rowCount()) {
            return false;
        }
        //从仓库与用户表中删除
        $result = $this->database_medoo->delete("user_repository", [
            "AND" => [
                "repositoryid" => $repositoryid
            ]
        ]);
        return true;
    }

    //写入账户和密码的初始内容到仓库中的passowrd和authz配置文件，进行仓库初始化
    private function InitRepositoryConfFile($repository_name) {
        //将以下内容写入authz文件
        /*
         * [aliases]
         * [groups]
         * [/]
         * root=rw 
         */
        $con = "[aliases]\n\n[groups]\n\n[/]\nroot = rw";
        parent::RequestReplyExec(' echo \'' . $con . '\' > ' . $this->svn_repository_path . '/' . $repository_name . '/conf/authz');
        //将以下内容写入passwd文件
        /*
         * [users]
         * root=随机生成的密码
         */
        $pass = trim($this->GetInitPasswd(16));
        $con = "[users]\nroot = " . $pass . "\n";
        parent::RequestReplyExec(' echo \'' . $con . '\' > ' . $this->svn_repository_path . '/' . $repository_name . '/conf/passwd');
        /*
         * 目录浏览
         */
        if ($this->Mod_dav_svn_status) {
            parent::RequestReplyExec(" htpasswd -m -b  " . $this->svn_repository_path . "/" . $repository_name . "/conf/http_passwd root $pass");
        }
    }

    //向仓库表中写入仓库信息
    private function InsertRepositoryTable($repository_name) {
        $repository_name = trim($repository_name);
        if ($repository_name == "" || $repository_name == null) {
            return false;
        }
        $this->database_medoo->insert("repository", ["repository_name" => $repository_name, "repository_edittime" => date("Y-m-d-H-i-s")]);
        $account_id = $this->database_medoo->id();
        return $account_id;
    }

    //扫描仓库信息并更新仓库信息表
    private function UpdateRepositoryInfo() {
        //查仓库表
        $list = $this->database_medoo->select("repository", [
            "id",
            "repository_name"
        ]);
        //循环更新数据库（虽然循环更新数据库有点扯蛋 但是这是目前能想到的最好的方法 后期有待优化）
        foreach ($list as $key => $value) {
            $id = $list[$key]["id"];
            $repository_name = $list[$key]["repository_name"];
            $repository_url = $this->svn_repository_path . '/' . $repository_name;
            $repository_size = round($this->GetDirSize($this->svn_repository_path . '/' . $repository_name) / (1024 * 1024), 2);
            $repository_checkout_url = 'svn://' . $this->server_domain . '/' . $repository_name;
            if ($this->Mod_dav_svn_status) {
                $repository_web_url = $this->protocol . "://" . $this->server_domain . $this->svn_web_path . '/' . $repository_name;
            } else {
                $repository_web_url = "-";
            }
            $result = $this->database_medoo->update("repository", [
                "repository_url" => $repository_url,
                "repository_size" => $repository_size,
                "repository_checkout_url" => $repository_checkout_url,
                "repository_web_url" => $repository_web_url
                    ], [
                "id" => $id
            ]);
        }
    }

    //扫描仓库并写入仓库表
    private function ScanRepository() {
        $file_arr = scandir($this->svn_repository_path);
        foreach ($file_arr as $file_item) {
            if ($file_item != '.' && $file_item != '..') {
                if (is_dir($this->svn_repository_path . '/' . $file_item)) {
                    $file_arr2 = scandir($this->svn_repository_path . '/' . $file_item);
                    foreach ($file_arr2 as $file_item2) {
                        if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                            $result = $this->database_medoo->insert("repository", [
                                "repository_name" => $file_item,
                                "repository_edittime" => date("Y-m-d-H-i-s")
                            ]);
                            break;
                        }
                    }
                }
            }
        }
    }

    //向仓库表中更新仓库信息
    private function UpdateRepositoryName($old_repository_name, $new_repository_name) {
        $old_repository_name = trim($old_repository_name);
        $new_repository_name = trim($new_repository_name);
        if ($old_repository_name == "" || $old_repository_name == null || $new_repository_name == "" || $new_repository_name == null) {
            return false;
        }
        //更新仓库表
        $result = $this->database_medoo->select("repository", ["id"], ["repository_name" => $old_repository_name]);
        if (empty($result)) {
            return false;
        }
        $repositoryid = $result[0]['id']; //仓库id
        $result = $this->database_medoo->update("repository", ["repository_name" => $new_repository_name], ["id" => $repositoryid]);
        if (!$result->rowCount()) {
            return false;
        }
        return true;
    }

    //卸载程序时要清空仓库表和用户-仓库表
    private function TruncateTable() {
        $arr = array(
            "repository",
            "user_repository"
        );
        foreach ($arr as $value) {
            $this->database_medoo->query("truncate table $value;");
        }
    }

}
