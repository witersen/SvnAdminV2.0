<?php

class personal extends controller
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

    /**
     * 管理人员修改自己的账号
     */
    function EditAdminUserName()
    {
        if ($this->requestPayload['userName'] != $this->requestPayload['confirm']) {
            FunMessageExit(200, 0, '输入不一致');
        }

        if (trim($this->requestPayload['userName']) == '') {
            FunMessageExit(200, 0, '用户名不合法');
        }

        $info = $this->database->get('admin_users', [
            'admin_user_name'
        ], [
            'admin_user_name' => $this->requestPayload['userName']
        ]);
        if ($info != null) {
            FunMessageExit(200, 0, '与已有用户冲突');
        }

        $this->database->update('admin_users', [
            'admin_user_name' => $this->requestPayload['userName']
        ], [
            'admin_user_name' => $this->globalUserName
        ]);

        FunMessageExit();
    }

    /**
     * 管理人员修改自己的密码
     */
    function EditAdminUserPass()
    {
        if ($this->requestPayload['password'] != $this->requestPayload['confirm']) {
            FunMessageExit(200, 0, '输入不一致');
        }

        if (trim($this->requestPayload['password']) == '') {
            FunMessageExit(200, 0, '密码不合法');
        }

        $this->database->update('admin_users', [
            'admin_user_pass' => $this->requestPayload['password']
        ], [
            'admin_user_name' => $this->globalUserName
        ]);

        FunMessageExit();
    }

    /**
     * SVN用户修改自己的密码
     */
    function EditSvnUserPass()
    {
        if ($this->requestPayload['newPassword'] != $this->requestPayload['confirm']) {
            FunMessageExit(200, 0, '输入不一致');
        }

        //获取SVN指定用户的密码
        $result = FunGetPassByUser($this->globalPasswdContent, $this->globalUserName);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '用户不存在');
        }

        if (trim($this->requestPayload['newPassword']) == '') {
            FunMessageExit(200, 0, '密码不合法');
        }

        if ($result != $this->requestPayload['oldPassword']) {
            FunMessageExit(200, 0, '旧密码输入错误');
        }

        //修改SVN指定用户的密码
        $result = FunUpdSvnUserPass($this->globalPasswdContent, $this->globalUserName, $this->requestPayload['newPassword']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '用户不存在');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_PASSWD_FILE);

        $this->database->update('svn_users', [
            'svn_user_pass' => $this->requestPayload['newPassword']
        ], [
            'svn_user_name' => $this->globalUserName
        ]);

        FunMessageExit();
    }
}
