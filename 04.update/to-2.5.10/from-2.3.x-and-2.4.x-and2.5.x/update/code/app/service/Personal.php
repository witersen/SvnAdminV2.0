<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use app\service\Apache as ServiceApache;

class Personal extends Base
{
    /**
     * 其它服务层对象
     *
     * @var object
     */
    private $Mail;
    private $ServiceApache;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->Mail = new Mail($parm);
        $this->ServiceApache = new ServiceApache($parm);
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
        if ($this->enableCheckout == 'svn') {
            $dataSource = $this->svnDataSource;
        } else {
            $dataSource = $this->httpDataSource;
        }

        if ($dataSource['user_source'] == 'ldap') {
            return message(200, 0, '当前SVN用户来源为LDAP-不支持此操作');
        }

        if ($this->payload['newPassword'] != $this->payload['confirm']) {
            return message(200, 0, '输入不一致');
        }

        if (trim($this->payload['newPassword']) == '') {
            return message(200, 0, '密码不合法');
        }

        if ($this->enableCheckout == 'svn') {
            $result = $this->SVNAdmin->UpdUserPass($this->passwdContent, $this->userName, $this->payload['newPassword']);
            if (is_numeric($result)) {
                if ($result == 621) {
                    return message(200, 0, '文件格式错误(不存在[users]标识)');
                } elseif ($result == 710) {
                    return message(200, 0, '用户不存在 请管理员同步用户后重试');
                } else {
                    return message(200, 0, "错误码$result");
                }
            }

            funFilePutContents($this->configSvn['svn_passwd_file'], $result);
        } else {
            $result = $this->ServiceApache->UpdUserPass($this->userName, $this->payload['newPassword']);
            if ($result['status'] != 1) {
                return message2($result);
            }
        }

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
     */
    public function UpdSubadminUserPass()
    {
        if ($this->payload['password'] != $this->payload['confirm']) {
            return message(200, 0, '输入不一致');
        }

        if (trim($this->payload['password']) == '') {
            return message(200, 0, '密码不合法');
        }

        $this->database->update('subadmin', [
            'subadmin_password' => md5($this->payload['password'])
        ], [
            'subadmin_name' => $this->userName
        ]);

        //邮件
        $this->Mail->SendMail('Personal/UpdSubadminUserPass', '子管理员修改密码通知', '账号：' . $this->userName . ' '  . '时间：' . date('Y-m-d H:i:s'));

        return message(200, 1, '修改密码成功');
    }
}
