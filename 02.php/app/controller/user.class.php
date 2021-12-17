<?php

/*
 * 与用户操作相关
 */

class User extends Controller
{
    private $Mail;

    function __construct()
    {
        /*
         * 避免子类的构造函数覆盖父类的构造函数
         */
        parent::__construct();

        /*
         * 其它自定义操作
         */
        $this->Config = new Config();

        $this->Mail = new Mail();
    }

    //用户登录
    function Login($requestPayload)
    {
        $username = trim($requestPayload['username']);
        $password = trim($requestPayload['password']);

        if (empty($username) || empty($password)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整';
            return $data;
        }

        if ($username == MANAGE_USER) {
            if ($password == MANAGE_PASS) {
                $token = CreateToken($username);
                //发送邮件
                $time = date("Y-m-d-H-i-s");
                $ip = $send_content = ""
                    . "登录用户：$username \n"
                    . "服务器已设置域名：" . SERVER_DOMAIN . " \n"
                    . "服务器已设置IP地址：" . SERVER_IP . " \n"
                    . "当前时间：$time";
                $send_title = "SVN系统登录通知";
                $this->Mail->SendMail($send_title, $send_content);

                //返回成功信息
                $data['status'] = 1;
                $data['code'] = 200;
                $data['username'] = $username;
                $data['roleid'] = 1;
                $data['rolename'] = '管理员';
                $data['token'] = $token;
                $data['message'] = '登录成功';
                return $data;
            } else {
                $data['status'] = 0;
                $data['message'] = '密码错误';
                return $data;
            }
        }

        $svn_user_list = FunGetSvnUserList(file_get_contents(SVN_SERVER_PASSWD));
        if (!in_array($username, $svn_user_list)) {
            $data['status'] = 0;
            $data['message'] = '用户不存在';
            return $data;
        } else {
            $svn_user_pass_list = FunGetSvnUserPassList(file_get_contents(SVN_SERVER_PASSWD));
            if ($svn_user_pass_list[$username] == $password) {
                $token = CreateToken($username);
                //发送邮件
                $time = date("Y-m-d-H-i-s");
                $ip = $send_content = ""
                    . "登录用户：$username \n"
                    . "服务器已设置域名：" . SERVER_DOMAIN . " \n"
                    . "服务器已设置IP地址：" . SERVER_IP . " \n"
                    . "当前时间：$time";
                $send_title = "SVN系统登录通知";
                $this->Mail->SendMail($send_title, $send_content);

                //返回成功信息
                $data['status'] = 1;
                $data['code'] = 200;
                $data['userid'] = 0;
                $data['username'] = $username;
                $data['roleid'] = 2;
                $data['rolename'] = '用户';
                $data['token'] = $token;
                $data['message'] = '登录成功';
                return $data;
            } else {
                $data['status'] = 0;
                $data['message'] = '密码错误';
                return $data;
            }
        }

        $data['status'] = 0;
        $data['message'] = '用户不存在';
        return $data;
    }

    //邮箱检查
    function CheckMail($mail)
    {
        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/";
        preg_match($pattern, $mail, $matches);
        $flag = false;
        if (!empty($matches)) {
            $flag = true;
        }
        return $flag;
    }
}
