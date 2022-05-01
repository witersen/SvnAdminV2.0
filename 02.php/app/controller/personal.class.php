<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-04-27 16:40:15
 * @Description: QQ:1801168257
 */

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
        if ($this->payload['userName'] != $this->payload['confirm']) {
            FunMessageExit(200, 0, '输入不一致');
        }

        if (trim($this->payload['userName']) == '') {
            FunMessageExit(200, 0, '用户名不合法');
        }

        $info = $this->database->get('admin_users', [
            'admin_user_name'
        ], [
            'admin_user_name' => $this->payload['userName']
        ]);
        if ($info != null) {
            FunMessageExit(200, 0, '与已有用户冲突');
        }

        $this->database->update('admin_users', [
            'admin_user_name' => $this->payload['userName']
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
        if ($this->payload['password'] != $this->payload['confirm']) {
            FunMessageExit(200, 0, '输入不一致');
        }

        if (trim($this->payload['password']) == '') {
            FunMessageExit(200, 0, '密码不合法');
        }

        $this->database->update('admin_users', [
            'admin_user_pass' => $this->payload['password']
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
        if ($this->payload['newPassword'] != $this->payload['confirm']) {
            FunMessageExit(200, 0, '输入不一致');
        }

        //获取SVN指定用户的密码
        $result = $this->SVNAdminUser->GetPassByUser($this->globalPasswdContent, $this->globalUserName);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '用户不存在');
        }

        if (trim($this->payload['newPassword']) == '') {
            FunMessageExit(200, 0, '密码不合法');
        }

        if ($result != $this->payload['oldPassword']) {
            FunMessageExit(200, 0, '旧密码输入错误');
        }

        //修改SVN指定用户的密码
        $result = $this->SVNAdminUser->UpdSvnUserPass($this->globalPasswdContent, $this->globalUserName, $this->payload['newPassword']);
        if ($result == '0') {
            FunMessageExit(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            FunMessageExit(200, 0, '用户不存在');
        }

        FunShellExec('echo \'' . $result . '\' > ' . SVN_PASSWD_FILE);

        $this->database->update('svn_users', [
            'svn_user_pass' => $this->payload['newPassword']
        ], [
            'svn_user_name' => $this->globalUserName
        ]);

        FunMessageExit();
    }
}
