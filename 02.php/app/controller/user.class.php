<?php

/*
 * 与用户操作相关的方法的封装
 */

class User extends Controller
{
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

    private $Mail;
    private $server_ip;
    private $server_domain;

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

        $this->server_domain = $this->Config->Get("SERVER_DOMAIN");
        $this->server_ip = $this->Config->Get("SERVER_IP");
    }

    //获取管理员为普通用户授权的仓库列表，显示所有仓库名称，根据用户，对应显示有没有权限
    function GetUserRepositoryList($requestPayload)
    {
        $userid = $requestPayload['userid'];

        //所有仓库列表（带仓库id）
        $all_list = $this->database_medoo->select("repository", ["id(repository_id)", "repository_name"], "*");
        //用户的所有仓库列表
        $user_list = $this->database_medoo->select('user_repository', [
            "[>]repository" => ["repositoryid" => "id"],
        ], [
            "user_repository.id",
            "user_repository.userid",
            "user_repository.repositoryid",
            "repository.repository_name",
        ], [
            "userid" => $userid,
        ]);
        //聚合
        for ($i = 0; $i < sizeof($all_list); $i++) {
            $all_list[$i]['privilege'] = "0";
            $all_list[$i]['id'] = $i;
        }
        for ($i = 0; $i < sizeof($user_list); $i++) {
            foreach ($all_list as $key => $value) {
                if ($all_list[$key]['repository_id'] == $user_list[$i]['repositoryid']) {
                    $all_list[$key]['privilege'] = "1";
                    break;
                }
            }
        }

        $data['status'] = 1;
        $data['message'] = '获取普通用户对应的仓库权限列表成功';
        $data['data'] = $all_list;
        return $data;
    }

    //设置普通用户授权的仓库列表
    function SetUserRepositoryList($requestPayload)
    {
        $userid = $requestPayload['userid'];
        $this_account_list = $requestPayload['this_account_list'];

        foreach ($this_account_list as $key => $value) {
            if ($value['privilege'] == "1") { //判断该用户是否包含该仓库记录，如果包含不做操作，否则进行插入
                $result = $this->database_medoo->select("user_repository", ["id"], ["userid" => $userid, "repositoryid" => $value['repository_id']]);
                if (empty($result)) { //不包含
                    $result = $this->database_medoo->insert("user_repository", ["userid" => $userid, "repositoryid" => $value['repository_id']]);
                    if (!$result->rowCount()) {
                        $data['status'] = 0;
                        $data['message'] = '修改普通用户对应的仓库权限列表失败';
                        return $data;
                    }
                }
            } elseif ($value['privilege'] == "0") { //判断该用户是否包含该仓库记录，如果不包含不做操作，否则进行删除
                $result = $this->database_medoo->select("user_repository", ["id"], ["userid" => $userid, "repositoryid" => $value['repository_id']]);
                if (!empty($result)) { //包含
                    $result = $this->database_medoo->delete("user_repository", [
                        "AND" => [
                            "userid" => $userid, "repositoryid" => $value['repository_id']
                        ]
                    ]);
                    if (!$result->rowCount()) {
                        $data['status'] = 0;
                        $data['message'] = '修改普通用户对应的仓库权限列表失败';
                        return $data;
                    }
                }
            }
        }
        $data['status'] = 1;
        //        $data['message'] = '修改普通用户对应的仓库权限列表成功';
        $data['message'] = '授权成功';
        return $data;
    }

    //用户登录
    function Login($requestPayload)
    {
        $username = trim($requestPayload['username']);
        $password = trim($requestPayload['password']);

        if (empty($username) || empty($password)) {
            $data['status'] = 0;
            $data['message'] = '登录失败 参数不完整';
            return $data;
        }

        $result = $this->database_medoo->select('user', [
            "[>]role" => ["roleid" => "id"],
        ], [
            "user.id(userid)",
            "user.username",
            "user.roleid",
            "user.password",
            "role.rolename"
        ], [
            "username" => $username,
            "password" => $password
        ]);

        if (empty($result)) {
            $data['status'] = 0;
            $data['message'] = '登录失败 用户不存在或密码错误';
            return $data;
        }
        $token = CreateToken($result[0]['userid']);

        //发送邮件
        $time = date("Y-m-d-H-i-s");
        $ip = $send_content = ""
            . "登录用户：$username \n"
            . "登录用户uid：" . $result[0]['userid'] . " \n"
            . "服务器已设置域名：$this->server_domain \n"
            . "服务器已设置IP地址：$this->server_ip \n"
            . "当前时间：$time";
        $send_title = "SVN系统登录通知";
        $receive_roleid = 2;
        $receive_userid = 1;
        $this->Mail->SendMail($send_title, $send_content, $receive_roleid, $receive_userid);

        //返回成功信息
        $data['status'] = 1;
        $data['code'] = 200;
        $data['userid'] = $result[0]['userid'];
        $data['username'] = $result[0]['username'];
        $data['roleid'] = $result[0]['roleid'];
        $data['rolename'] = $result[0]['rolename'];
        $data['token'] = $token;
        $data['message'] = '登录成功';
        return $data;
    }

    //用户注销
    function LogOut()
    {
    }

    //修改用户信息
    function EditUser($requestPayload)
    {
        $userid = $this->this_userid;
        $edit_userid = trim($requestPayload['edit_userid']);
        $edit_username = trim($requestPayload['edit_username']);
        $edit_password = trim($requestPayload['edit_password']);
        $edit_password2 = trim($requestPayload['edit_password2']);
        $edit_roleid = trim($requestPayload['edit_roleid']);
        $edit_realname = trim($requestPayload['edit_realname']);
        $edit_email = trim($requestPayload['edit_email']);
        $edit_phone = trim($requestPayload['edit_phone']);

        if (empty($edit_userid) || empty($edit_username) || empty($edit_password) || empty($edit_roleid) || $edit_password != $edit_password2) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        $info = $this->database_medoo->select('user', ["id"], ["id" => $edit_userid]);
        if (empty($info)) {
            $data['status'] = 0;
            $data['message'] = '修改失败 用户不存在';
            return $data;
        }

        $info = $this->database_medoo->select('user', ["roleid", "username"], ["id" => $userid]);
        if ($userid == $edit_userid && $info[0]['roleid'] !== $edit_roleid) {
            $data['status'] = 0;
            $data['message'] = '修改失败 不可修改自身的角色';
            return $data;
        }

        if ($info[0]['roleid'] == 1 && $edit_roleid == 1 && $info[0]['username'] != $edit_username) {
            $data['status'] = 0;
            $data['message'] = '修改失败 超级管理员用户名不可修改';
            return $data;
        }

        $info = $this->database_medoo->update("user", [
            "username" => $edit_username,
            "password" => $edit_password,
            "roleid" => $edit_roleid,
            "realname" => $edit_realname,
            "email" => $edit_email,
            "phone" => $edit_phone,
        ], ["id" => $edit_userid]);

        if (!$info->rowCount()) {
            $data['status'] = 1;
            $data['message'] = '未作任何修改';
            return $data;
        }

        $data['status'] = 1;
        $data['message'] = '修改用户信息成功';
        return $data;
    }

    //删除用户
    function DelUser($requestPayload)
    {
        $del_userid = $requestPayload['del_userid'];
        $this_userid = $this->this_userid;
        $this_username = $this->this_username;

        if (empty($del_userid) || empty($this_userid)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        $info = $this->database_medoo->select('user', ["id", "username"], ["id" => $del_userid]);
        if (empty($info)) {
            $data['status'] = 0;
            $data['message'] = '删除用户失败 用户不存在';
            return $data;
        }

        if ($info[0]['username'] == "admin") {
            $data['status'] = 0;
            $data['message'] = '删除用户失败 超级管理员不可删除';
            return $data;
        }

        if ($del_userid == $this_userid) {
            $data['status'] = 0;
            $data['message'] = '删除用户失败 不能删除自身';
            return $data;
        }

        //删除用户-仓库表中数据
        $this->database_medoo->delete("user_repository", [
            "AND" => [
                "userid" => $del_userid
            ]
        ]);

        //删除用户表中数据
        $info = $this->database_medoo->delete("user", [
            "AND" => [
                "id" => $del_userid
            ]
        ]);
        if (!$info->rowCount()) {
            $data['status'] = 0;
            $data['message'] = '删除用户失败';
            return $data;
        }

        //发送邮件
        $time = date("Y-m-d-H-i-s");
        $send_content = ""
            . "被删除用户的用户id：$del_userid \n"
            . "操作用户：$this_username \n"
            . "操作用户uid：$this_userid \n"
            . "服务器已设置域名：$this->server_domain \n"
            . "服务器已设置IP地址：$this->server_ip \n"
            . "当前时间：$time";
        $send_title = "用户删除通知";
        $receive_roleid = 2;
        $receive_userid = 1;
        $this->Mail->SendMail($send_title, $send_content, $receive_roleid, $receive_userid);

        $data['status'] = 1;
        $data['message'] = '删除用户成功';
        return $data;
    }

    //添加用户
    function AddUser($requestPayload)
    {
        $username = trim($requestPayload['username']);
        $password = trim($requestPayload['password']);
        $password2 = trim($requestPayload['password2']);
        $roleid = trim($requestPayload['roleid']);
        $realname = trim($requestPayload['realname']);
        $email = trim($requestPayload['email']);
        $phone = trim($requestPayload['phone']);
        $this_userid = $this->this_userid;
        $this_username = $this->this_username;

        if (empty($username) || empty($password) || empty($roleid) || $password != $password2) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        if ($email != "" && !$this->CheckMail($email)) {
            $data['status'] = 0;
            $data['message'] = '邮箱格式填写错误';
            return $data;
        }

        $info = $this->database_medoo->select('user', ["id"], ["username" => $username]);
        if (!empty($info)) {
            $data['status'] = 0;
            $data['message'] = '添加失败 用户已存在';
            return $data;
        }

        if ($roleid == 1) {
            $data['status'] = 0;
            $data['message'] = '添加失败 超级管理员不可添加';
            return $data;
        }

        $info = $this->database_medoo->insert("user", [
            "username" => $username,
            "password" => $password,
            "roleid" => $roleid,
            "realname" => $realname,
            "email" => $email,
            "phone" => $phone,
            "add_time" => date("Y-m-d-H-i-s")
        ]);
        if (!$info) {
            $data['status'] = 0;
            $data['message'] = '添加失败';
            return $data;
        }

        //发送邮件
        $time = date("Y-m-d-H-i-s");
        $send_content = ""
            . "被创建用户的用户名：$username \n"
            . "被创建用户的roleid：$roleid \n"
            . "操作用户：$this_username \n"
            . "操作用户uid：$this_userid \n"
            . "服务器已设置域名：$this->server_domain \n"
            . "服务器已设置IP地址：$this->server_ip \n"
            . "当前时间：$time";
        $send_title = "新用户创建通知";
        $receive_roleid = 2;
        $receive_userid = 1;
        $this->Mail->SendMail($send_title, $send_content, $receive_roleid, $receive_userid);

        $data['status'] = 1;
        $data['message'] = '添加用户成功';
        return $data;
    }

    //获取用户列表
    function GetUserList($requestPayload)
    {
        $pageSize = $requestPayload['pageSize'];
        $currentPage = $requestPayload['currentPage'];
        $userid = $this->this_userid;

        if (empty($pageSize) || empty($currentPage) || empty($userid)) {
            $data['status'] = 0;
            $data['message'] = '参数不完整或错误';
            return $data;
        }

        $info = $this->database_medoo->select('user', ["roleid"], ["id" => $userid]);
        if (empty($info)) {
            $data['status'] = 0;
            $data['message'] = '获取用户列表失败 非法用户';
            $data['code'] = 401;
            return $data;
        }

        $roleid = $info[0]["roleid"];

        if ($roleid == 1) {
            //分页处理
            $begin = $pageSize * ($currentPage - 1);

            $info = $this->database_medoo->select('user', [
                "[>]role" => ["roleid" => "id"],
            ], [
                "user.id(uid)",
                "user.roleid",
                "user.username",
                "user.password",
                "user.realname",
                "user.email",
                "user.phone",
                "role.rolename"
            ], [
                "LIMIT" => [$begin, $pageSize],
                "ORDER" => ["user.add_time" => "ASC"],
            ]);

            $total = $this->database_medoo->count("user");

            for ($i = 0; $i < sizeof($info); $i++) {
                $info[$i]['id'] = $i + $begin;
            }

            $data['status'] = 1;
            $data['message'] = '获取用户列表成功';
            $data['data'] = $info;
            $data['total'] = $total;
            return $data;
        } elseif ($roleid == 2) {
            //分页处理
            $begin = $pageSize * ($currentPage - 1);

            $info = $this->database_medoo->select('user', [
                "[>]role" => ["roleid" => "id"],
            ], [
                "user.id(uid)",
                "user.roleid",
                "user.username",
                "user.password",
                "user.realname",
                "user.email",
                "user.phone",
                "role.rolename"
            ], [
                "AND" => [
                    "OR" => [
                        "user.id" => $userid,
                        "user.roleid" => 3,
                    ],
                ],
                "LIMIT" => [$begin, $pageSize],
                "ORDER" => ["user.add_time" => "DESC"],
            ]);

            $total = $this->database_medoo->count('user', [
                "OR" => [
                    "user.id" => $userid,
                    "user.roleid" => 3,
                ],
            ]);

            for ($i = 0; $i < sizeof($info); $i++) {
                $info[$i]['id'] = $i + $begin;
            }

            $data['status'] = 1;
            $data['message'] = '获取用户列表成功';
            $data['data'] = $info;
            $data['total'] = $total;
            return $data;
        } else {
            $data['status'] = 0;
            $data['message'] = '获取用户列表失败 非法用户';
            $data['code'] = 401;
            return $data;
        }
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
