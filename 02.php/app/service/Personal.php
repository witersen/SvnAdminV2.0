<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

class Personal extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $Mail;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->Mail = new Mail();
    }

    /**
     * 管理人员修改自己的账号
     */
    public function EditAdminUserName()
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

        //邮件
        $this->Mail->SendMail('Personal/EditAdminUserName', '管理人员修改账号通知', '原账号：' . $this->userName . ' ' . '新账号：' . $this->payload['userName'] . ' ' . '时间：' . date('Y-m-d H:i:s'));

        return message(200, 1, '修改密码成功');
    }

    /**
     * 管理人员修改自己的密码
     */
    public function EditAdminUserPass()
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

        //邮件
        $this->Mail->SendMail('Personal/EditAdminUserPass', '管理人员修改密码通知', '账号：' . $this->userName . ' '  . '时间：' . date('Y-m-d H:i:s'));

        return message(200, 1, '修改密码成功');
    }

    /**
     * SVN用户修改自己的密码
     */
    public function EditSvnUserPass()
    {
        if ($this->payload['newPassword'] != $this->payload['confirm']) {
            return message(200, 0, '输入不一致');
        }

        //获取SVN指定用户的密码
        $result = $this->SVNAdmin->GetUserInfo($this->passwdContent, $this->userName);
        if (is_numeric($result)) {
            if ($result == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($result == 710) {
                return message(200, 0, '用户不存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        if (trim($this->payload['newPassword']) == '') {
            return message(200, 0, '密码不合法');
        }

        if ($result['userPass'] != $this->payload['oldPassword']) {
            return message(200, 0, '旧密码输入错误');
        }

        //修改SVN指定用户的密码
        $result = $this->SVNAdmin->UpdUserPass($this->passwdContent, $this->userName, $this->payload['newPassword']);
        if (is_numeric($result)) {
            if ($result == 621) {
                return message(200, 0, '文件格式错误(不存在[users]标识)');
            } else if ($result == 710) {
                return message(200, 0, '用户不存在');
            } else {
                return message(200, 0, "错误码$result");
            }
        }

        funFilePutContents($this->configSvn['svn_passwd_file'], $result);

        $this->database->update('svn_users', [
            'svn_user_pass' => $this->payload['newPassword']
        ], [
            'svn_user_name' => $this->userName
        ]);

        //邮件
        $this->Mail->SendMail('Personal/EditSvnUserPass', 'SVN用户修改密码通知', '账号：' . $this->userName . ' ' . '新密码：' . $this->payload['newPassword'] . ' ' . '时间：' . date('Y-m-d H:i:s'));

        return message(200, 1, '修改密码成功');
    }

    /**
     * 子管理员修改自己的密码
     *
     * @return void
     */
    public function UpdSubadminUserPass(){}
}
