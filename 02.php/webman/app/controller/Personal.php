<?php
/*
 * @Author: witersen
 * @Date: 2022-04-24 23:37:05
 * @LastEditors: witersen
 * @LastEditTime: 2022-05-04 19:47:46
 * @Description: QQ:1801168257
 */

namespace app\controller;

use support\Request;

class Personal extends Core
{
    /**
     * 管理人员修改自己的账号
     */
    public function EditAdminUserName(Request $request)
    {
        if ($this->payload['userName'] != $this->payload['confirm']) {
            return message(200, 0, '输入不一致');
        }

        if (trim($this->payload['userName']) == '') {
            return message(200, 0, '用户名不合法');
        }

        $info = $this->database->get('admin_users', [
            'admin_user_name'
        ], [
            'admin_user_name' => $this->payload['userName']
        ]);
        if ($info != null) {
            return message(200, 0, '与已有用户冲突');
        }

        $this->database->update('admin_users', [
            'admin_user_name' => $this->payload['userName']
        ], [
            'admin_user_name' => $this->userName
        ]);

        return message(200, 1, '修改密码成功');
    }

    /**
     * 管理人员修改自己的密码
     */
    public function EditAdminUserPass(Request $request)
    {
        if ($this->payload['password'] != $this->payload['confirm']) {
            return message(200, 0, '输入不一致');
        }

        if (trim($this->payload['password']) == '') {
            return message(200, 0, '密码不合法');
        }

        $this->database->update('admin_users', [
            'admin_user_password' => $this->payload['password']
        ], [
            'admin_user_name' => $this->userName
        ]);

        return message(200, 1, '修改密码成功');
    }

    /**
     * SVN用户修改自己的密码
     */
    public function EditSvnUserPass(Request $request)
    {
        if ($this->payload['newPassword'] != $this->payload['confirm']) {
            return message(200, 0, '输入不一致');
        }

        //获取SVN指定用户的密码
        $result = $this->SVNAdminUser->GetPassByUser($this->passwdContent, $this->userName);
        if ($result == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            return message(200, 0, '用户不存在');
        }

        if (trim($this->payload['newPassword']) == '') {
            return message(200, 0, '密码不合法');
        }

        if ($result != $this->payload['oldPassword']) {
            return message(200, 0, '旧密码输入错误');
        }

        //修改SVN指定用户的密码
        $result = $this->SVNAdminUser->UpdSvnUserPass($this->passwdContent, $this->userName, $this->payload['newPassword']);
        if ($result == '0') {
            return message(200, 0, '文件格式错误(不存在[users]标识)');
        }
        if ($result == '1') {
            return message(200, 0, '用户不存在');
        }

        passthru('echo \'' . $result . '\' > ' . $this->config_svnadmin_svn['svn_passwd_file']);

        $this->database->update('svn_users', [
            'svn_user_pass' => $this->payload['newPassword']
        ], [
            'svn_user_name' => $this->userName
        ]);

        return message(200, 1, '修改密码成功');
    }
}
