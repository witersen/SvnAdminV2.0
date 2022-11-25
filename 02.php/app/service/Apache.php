<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace app\service;

use app\service\Svn as ServiceSvn;

class Apache extends Base
{
    /**
     * 服务层对象
     *
     * @var object
     */
    private $ServiceSvn;

    function __construct($parm = [])
    {
        parent::__construct($parm);

        $this->ServiceSvn = new ServiceSvn($parm);
    }

    /**
     * 获取 apache 服务器信息
     *
     * @return void
     */
    public function GetApacheInfo()
    {
        //获取 httpd 协议启用状态
        $result = $this->ServiceSvn->GetPasswddbInfo('httpPasswd');
        if (is_numeric($result)) {
            return message(200, 0, sprintf('获取[%s]配置信息失败-请及时检查[%s-%s]', $this->configSvn['svn_conf_file'], 2, $result));
        }

        return message(200, 1, '成功', [
            'version' => funShellExec(sprintf("'%s' -v", $this->configBin['httpd']))['result'],
            'modules' => implode(',', explode("\n", trim(funShellExec(sprintf("ls '%s' | grep svn", $this->configSvn['apache_modules_path']))['result']))),
            'modulesPath' => $this->configSvn['apache_modules_path'],
            'passwordDb' => $this->configSvn['http_passwd_file'],
            'enable' => $result
        ]);
    }

    /**
     * 写入 subversion.conf 配置文件
     *
     * @return void
     */
    private function UpdSubversionInfo()
    {
        $templeteSubversionPath = BASE_PATH . '/templete/apache/subversion.conf';
        if (!is_readable($templeteSubversionPath)) {
            return message(200, 0, sprintf('文件[%s]不可读或不存在', $templeteSubversionPath));
        }

        $prefix = '/svn';
        $DAV = 'svn';
        $SVNListParentPath = 'on';
        $SVNParentPath = $this->configSvn['rep_base_path'];
        $AuthType = 'Basic';
        $AuthName = "SVN Repo";
        $AuthUserFile = $this->configSvn['http_passwd_file'];
        $AuthzSVNAccessFile = $this->configSvn['svn_authz_file'];
        $Require = 'valid-user';

        $subversion = sprintf(
            file_get_contents($templeteSubversionPath),
            $prefix,
            $DAV,
            $SVNListParentPath,
            $SVNParentPath,
            $AuthType,
            $AuthName,
            $AuthUserFile,
            $AuthzSVNAccessFile,
            $Require
        );

        $subversionPath = $this->configSvn['apache_subversion_file'];
        if (!file_exists($subversionPath)) {
            @file_put_contents($subversionPath, $subversion);
            if (!file_exists($subversionPath)) {
                funFilePutContents($subversionPath, $subversion, true);
                if (!file_exists($subversionPath)) {
                    return message(200, 0, sprintf('无法创建文件[%s]', $subversionPath));
                }
            }
        }

        if (!is_writeable($subversionPath)) {
            funFilePutContents($subversionPath, $subversion, true);
            if (!is_writeable($subversionPath)) {
                return message(200, 0, sprintf('无法写入文件[%s]', $subversionPath));
            }
        }

        file_put_contents($subversionPath, $subversion);

        return message();
    }

    /**
     * 启用 http 协议检出
     *
     * @return void
     */
    public function UpdSubversionEnable()
    {
        //修改 svnserve.conf 为 httpPasswd
        $result = $this->ServiceSvn->UpdPasswddbInfo('httpPasswd');
        if (is_numeric($result)) {
            return message(200, 0, sprintf('更新[%s]配置信息失败-请及时检查[%s-%s]', $this->configSvn['svn_conf_file'], 2, $result));
        }
        file_put_contents($this->configSvn['svn_conf_file'], $result);

        //写入 subversion.conf
        $result = $this->UpdSubversionInfo();
        if ($result['status'] != 1) {
            return message($result['code'], $result['status'], $result['message'], $result['data']);
        }

        //重启 httpd
        funShellExec(sprintf("'%s' -k restart", $this->configBin['httpd']), true);

        return message();
    }

    /**
     * 用户登录认证
     *
     * @return void
     */
    public function Auth($username, $password)
    {
        $result = funShellExec(
            sprintf(
                "'%s' -bv '%s' '%s' '%s'",
                $this->configBin['htpasswd'],
                $this->configSvn['http_passwd_file'],
                $username,
                $password
            )
        );
        // if ($result['code'] != 0) {
        //     return message(200, 0, $result['error']);
        // }

        if (substr(trim($result['error']), -8) == 'correct.') {
            return message();
        }

        return message(200, 0, '登录失败[账号或密码错误]');
    }

    /**
     * 创建用户
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public function CreateUser($username, $password)
    {
        $result = funShellExec(
            sprintf(
                "'%s' -b '%s' '%s' '%s'",
                $this->configBin['htpasswd'],
                $this->configSvn['http_passwd_file'],
                $username,
                $password
            )
        );
        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        return message();
    }

    /**
     * 更新用户密码
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public function UpdUserPass($username, $password)
    {
        $result = funShellExec(
            sprintf(
                "'%s' -b '%s' '%s' '%s'",
                $this->configBin['htpasswd'],
                $this->configSvn['http_passwd_file'],
                $username,
                $password
            )
        );
        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        return message();
    }

    /**
     * 删除用户
     *
     * @param string $username
     * @return void
     */
    public function DelUser($username)
    {
        $result = funShellExec(
            sprintf(
                "'%s' -D '%s' '%s'",
                $this->configBin['htpasswd'],
                $this->configSvn['http_passwd_file'],
                $username
            )
        );
        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        return message();
    }

    /**
     * 生成用户账户密码字符串
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public function CreateUserStr($username, $password)
    {
        $result = funShellExec(
            sprintf(
                "'%s' '%s' '%s'",
                $this->configBin['htpasswd'],
                $username,
                $password
            )
        );
        if ($result['code'] != 0) {
            return message(200, 0, $result['error']);
        }

        return message(200, 1, '成功', $result['result']);
    }
}
