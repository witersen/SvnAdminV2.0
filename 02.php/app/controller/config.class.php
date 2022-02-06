<?php

declare(strict_types=1);

/*
 * 与配置信息操作相关
 */

class Config extends Controller
{

    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
    }

    //从配置文件获取
    public function Get($key)
    {
        $strContent = file_get_contents(BASE_PATH . '/config/auto.config.php');
        return FunGetConfigValue($strContent, $key);
    }

    //向配置文件更新
    public function Update($key, $value)
    {
        $strContent = file_get_contents(BASE_PATH . '/config/auto.config.php');
        $result = FunUpdateConfigValue($strContent, $key, $value);
        file_put_contents(BASE_PATH . '/config/auto.config.php', $result);
    }

    //设置管理员信息
    public function SetManageSetting($requestPayload)
    {
        $manageUser = trim($requestPayload['manageUser']);
        $managePass = trim($requestPayload['managePass']);
        $manageEmail = trim($requestPayload['manageEmail']);

        if (empty($manageUser) || empty($managePass) || empty($manageEmail)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        $this->Update("MANAGE_USER", $manageUser);
        $this->Update("MANAGE_PASS", $managePass);
        $this->Update("MANAGE_EMAIL", $manageEmail);

        $data['status'] = 1;
        $data['message'] = '成功';
        return $data;
    }

    //获取管理员信息
    public function GetManageSetting($requestPayload)
    {
        $result = array(
            "manageUser" => MANAGE_USER,
            "managePass" => MANAGE_PASS,
            "manageEmail" => MANAGE_EMAIL,
        );

        $data['status'] = 1;
        $data['data'] = $result;
        $data['message'] = '成功';
        return $data;
    }

    //获取服务器基本信息
    public function GetBasicSetting($requestPayload)
    {
        $all_mail_status = ALL_MAIL_STATUS == 1 ? true : false;
        $result = array(
            "server_ip" => SERVER_IP,
            "server_domain" => SERVER_DOMAIN,
            "svn_repository_path" => SVN_REPOSITORY_PATH,
            "backup_path" => BACKUP_PATH,
            "all_mail_status" => $all_mail_status,
            "token" => SIGNATURE,
            "logs" => LOG_PATH,
            "svnserve" => SVN_SERVER_CONF,
            "passwd" => SVN_SERVER_PASSWD,
            "authz" => SVN_SERVER_AUTHZ
        );

        $data['status'] = 1;
        $data['data'] = $result;
        $data['message'] = '成功';
        return $data;
    }

    //设置服务器基本信息
    public function SetBasicSetting($requestPayload)
    {
        $server_ip = trim($requestPayload['server_ip']);
        $token = trim($requestPayload['token']);
        $server_domain = trim($requestPayload['server_domain']);
        $all_mail_status = $requestPayload['all_mail_status'];

        if (empty($server_ip) || empty($server_domain)  || empty($token)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        $this->Update("SERVER_IP", $server_ip);
        $this->Update("SERVER_DOMAIN", $server_domain);
        $this->Update("SIGNATURE", $token);
        if ($all_mail_status) {
            $this->Update("ALL_MAIL_STATUS", 1);
        } else {
            $this->Update("ALL_MAIL_STATUS", 0);
        }

        $data['status'] = 1;
        $data['message'] = "成功";
        return $data;
    }

    //更改版本库父文件夹后触发的操作
    private function UpdateRepositoryParentPath($old_path, $new_path)
    {
        FunRequestReplyExec("mkdir $new_path");
        $info = FunRequestReplyExec('ps auxf|grep -v "grep"|grep svnserve');
        if ($info == ISNULL && !file_exists('/usr/bin/svnserve')) {
            /*
             * 没有安装过svn服务
             */
            //移动仓库
            $file_arr = scandir($old_path);
            foreach ($file_arr as $file_item) {
                if ($file_item != '.' && $file_item != '..') {
                    if (is_dir($old_path . '/' . $file_item)) {
                        $file_arr2 = scandir($old_path . '/' . $file_item);
                        foreach ($file_arr2 as $file_item2) {
                            if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                                FunRequestReplyExec('mv -b -f ' . $old_path . '/' . $file_item . ' ' . $new_path);
                            }
                        }
                    }
                }
            }
            //检查仓库是否已经被移动
            $file_arr = scandir($old_path);
            foreach ($file_arr as $file_item) {
                if ($file_item != '.' && $file_item != '..') {
                    if (is_dir($old_path . '/' . $file_item)) {
                        $file_arr2 = scandir($old_path . '/' . $file_item);
                        foreach ($file_arr2 as $file_item2) {
                            if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                                return false;
                            }
                        }
                    }
                }
            }
            return true;
        } else {
            /*
             * 安装过svn服务
             */
            //停止服务
            FunRequestReplyExec('systemctl stop svnserve');
            //移动仓库
            $file_arr = scandir($old_path);
            foreach ($file_arr as $file_item) {
                if ($file_item != '.' && $file_item != '..') {
                    if (is_dir($old_path . '/' . $file_item)) {
                        $file_arr2 = scandir($old_path . '/' . $file_item);
                        foreach ($file_arr2 as $file_item2) {
                            if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                                FunRequestReplyExec('mv -b -f ' . $old_path . '/' . $file_item . ' ' . $new_path);
                            }
                        }
                    }
                }
            }
            //修改配置文件
            FunRequestReplyExec('sed -i \'s/' . str_replace('/', '\/', $old_path) . '/' . str_replace('/', '\/', $new_path) . '/g\'' . ' /etc/sysconfig/svnserve'); //bug
            //启动服务
            FunRequestReplyExec('systemctl start svnserve');
            //检查配置文件是否被正确修改
            $file = fopen("/etc/sysconfig/svnserve", "r") or exit("无法打开文件!");
            $file_content = [];
            while (!feof($file)) {
                array_push($file_content, fgets($file));
            }
            fclose($file);
            foreach ($file_content as $key => $value) {
                if (strstr($value, $old_path)) {
                    return false;
                }
            }
            //检查仓库是否已经被移动
            $file_arr = scandir($old_path);
            foreach ($file_arr as $file_item) {
                if ($file_item != '.' && $file_item != '..') {
                    if (is_dir($old_path . '/' . $file_item)) {
                        $file_arr2 = scandir($old_path . '/' . $file_item);
                        foreach ($file_arr2 as $file_item2) {
                            if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                                return false;
                            }
                        }
                    }
                }
            }
            return true;
        }
    }
}
