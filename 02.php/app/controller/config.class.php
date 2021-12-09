
<?php

/*
 * 与配置信息操作相关的方法的封装
 */

class Config extends Controller {
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

    function __construct() {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
    }

    //获取指定项的值
    public function Get($key) {
        $result = $this->database_medoo->select("config", ["config_value"], ["config_name" => $key]);
        return $result[0]['config_value'];
    }

    //修改配置文件的值
    public function Update($key, $value) {
        $result = $this->database_medoo->update("config", ["config_value" => $value], ["config_name" => $key]);
    }

    //获取服务器基本信息
    public function GetBasicSetting($requestPayload) {
        $all_mail_status = $this->Get("ALL_MAIL_STATUS") == 1 ? true : false;
        $result = array(
            "server_ip" => $this->Get("SERVER_IP"),
            "server_domain" => $this->Get("SERVER_DOMAIN"),
            "svn_repository_path" => $this->Get("SVN_REPOSITORY_PATH"),
            "backup_path" => $this->Get("BACKUP_PATH"),
            "all_mail_status" => $all_mail_status
        );

        $data['status'] = 1;
        $data['data'] = $result;
        $data['message'] = '获取基础配置信息成功';
        return $data;
    }

    //设置服务器基本信息
    public function SetBasicSetting($requestPayload) {
        $server_ip = trim($requestPayload['server_ip']);
        $server_domain = trim($requestPayload['server_domain']);
        $svn_repository_path = trim($requestPayload['svn_repository_path']);
        $backup_path = trim($requestPayload['backup_path']);
        $all_mail_status = $requestPayload['all_mail_status'];

        if (empty($server_ip) || empty($server_domain) || empty($svn_repository_path) || empty($backup_path)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        $old_path = $this->Get("SVN_REPOSITORY_PATH");
        $new_path = $svn_repository_path;

        $UpdateRepositoryParentPath = true;
        if ($old_path != $new_path) {
            $UpdateRepositoryParentPath = $this->UpdateRepositoryParentPath($old_path, $new_path);
        }

        if ($UpdateRepositoryParentPath) {
            $this->Update("SERVER_IP", $server_ip);
            $this->Update("SERVER_DOMAIN", $server_domain);
            $this->Update("SVN_REPOSITORY_PATH", $svn_repository_path);
            $this->Update("BACKUP_PATH", $backup_path);
            if ($all_mail_status) {
                $this->Update("ALL_MAIL_STATUS", 1);
            } else {
                $this->Update("ALL_MAIL_STATUS", 0);
            }

            $data['status'] = 1;
            $data['message'] = "保存成功";
            return $data;
        } else {
            $data['status'] = 0;
            $data['message'] = "修改版本库父文件夹出错";
            return $data;
        }
    }

    //更改版本库父文件夹后触发的操作
    private function UpdateRepositoryParentPath($old_path, $new_path) {
        parent::RequestReplyExec("mkdir $new_path");
        $info = parent::RequestReplyExec('ps auxf|grep -v "grep"|grep svnserve');
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
                                parent::RequestReplyExec('mv -b -f ' . $old_path . '/' . $file_item . ' ' . $new_path);
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
            parent::RequestReplyExec('systemctl stop svnserve');
            //移动仓库
            $file_arr = scandir($old_path);
            foreach ($file_arr as $file_item) {
                if ($file_item != '.' && $file_item != '..') {
                    if (is_dir($old_path . '/' . $file_item)) {
                        $file_arr2 = scandir($old_path . '/' . $file_item);
                        foreach ($file_arr2 as $file_item2) {
                            if (($file_item2 == 'conf' || $file_item2 == 'db' || $file_item2 == 'hooks' || $file_item2 == 'locks')) {
                                parent::RequestReplyExec('mv -b -f ' . $old_path . '/' . $file_item . ' ' . $new_path);
                            }
                        }
                    }
                }
            }
            //修改配置文件
            parent::RequestReplyExec('sed -i \'s/' . str_replace('/', '\/', $old_path) . '/' . str_replace('/', '\/', $new_path) . '/g\'' . ' /etc/sysconfig/svnserve'); //bug
            //启动服务
            parent::RequestReplyExec('systemctl start svnserve');
            //检查配置文件是否被正确修改
            $file = fopen("/etc/sysconfig/svnserve", "r") or exit("无法打开文件!");
            $file_content = array();
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
